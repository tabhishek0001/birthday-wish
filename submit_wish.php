<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/bootstrap.php";

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$respond = static function (int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
};
$clean = static function ($value, int $limit): string {
    if (!is_string($value)) return '';
    return mb_substr(trim(str_replace("\0", '', $value)), 0, $limit, 'UTF-8');
};
$iniBytes = static function (string $value): int {
    $value = trim($value);
    if ($value === '') return 0;
    $unit = strtolower(substr($value, -1));
    $number = (float)$value;
    if ($unit === 'g') $number *= 1024;
    if ($unit === 'g' || $unit === 'm') $number *= 1024;
    if ($unit === 'g' || $unit === 'm' || $unit === 'k') $number *= 1024;
    return (int)$number;
};
$cleanupUploads = static function (string $directory, array $files): void {
    foreach ($files as $file) {
        if (is_string($file) && $file !== '') {
            $path = $directory . DIRECTORY_SEPARATOR . basename($file);
            if (is_file($path)) @unlink($path);
        }
    }
};

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $respond(405, ['success' => false, 'error' => 'Method not allowed.']);
}

// PHP empties both $_POST and $_FILES when the request exceeds post_max_size.
// Detect this before reporting misleading missing-field or internet errors.
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
$postLimit = $iniBytes((string)ini_get('post_max_size'));
if ($contentLength > 0 && $postLimit > 0 && $contentLength > $postLimit) {
    $respond(413, ['success' => false, 'error' => 'Total upload size exceeds the server limit. Please select fewer or smaller files.']);
}

$name = $clean($_POST['name'] ?? '', 80);
$message = $clean($_POST['message'] ?? '', 1200);
$pahchan = $clean($_POST['pahchan'] ?? '', 100);

if ($name === '' || in_array(mb_strtolower($name, 'UTF-8'), ['aapka naam', 'guest', 'your name'], true)) {
    $respond(422, ['success' => false, 'error' => 'Please enter your name before sending the wish.']);
}
if ($message === '') {
    $respond(422, ['success' => false, 'error' => 'Please add a birthday wish.']);
}
if (mb_strlen($message, 'UTF-8') < 2) {
    $respond(422, ['success' => false, 'error' => 'Please write a little more in your wish.']);
}

$now = time();
$last = (int)($_SESSION['birthday_last_wish_at'] ?? 0);
if ($last > 0 && ($now - $last) < 15) {
    $respond(429, ['success' => false, 'error' => 'Please wait a few seconds before sending another wish.']);
}

$mediaDir = __DIR__ . '/wish-media';
$uploadedMedia = [];
$savedMediaFiles = [];

if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'] ?? null)) {
    $upload = $_FILES['attachments'];
    $names = $upload['name'];
    $fileCount = count(array_filter($names, static fn($value) => is_string($value) && $value !== ''));

    if ($fileCount > 3) {
        $respond(422, ['success' => false, 'error' => 'Maximum 3 attachments are allowed.']);
    }

    if ($fileCount > 0) {
        if (!is_dir($mediaDir) && !@mkdir($mediaDir, 0755, true) && !is_dir($mediaDir)) {
            $respond(500, ['success' => false, 'error' => 'Media storage is unavailable. Please try again later.']);
        }

        $guardPath = $mediaDir . '/.htaccess';
        if (!is_file($guardPath)) {
            $guard = implode("\n", [
                'Options -Indexes',
                "<FilesMatch '(?i)^(\\.|.*\\.(php[0-9]?|phtml|phar|cgi|pl|py|sh|shtml))$'>",
                'Require all denied',
                '</FilesMatch>',
                ''
            ]);
            @file_put_contents($guardPath, $guard, LOCK_EX);
        }

        $allowed = [
            'image/jpeg' => ['jpg', 'image'],
            'image/png' => ['png', 'image'],
            'image/webp' => ['webp', 'image'],
            'image/gif' => ['gif', 'image'],
            'video/mp4' => ['mp4', 'video'],
            'video/webm' => ['webm', 'video'],
            'video/quicktime' => ['mov', 'video'],
            'audio/mpeg' => ['mp3', 'audio'],
            'audio/mp4' => ['m4a', 'audio'],
            'audio/x-m4a' => ['m4a', 'audio'],
            'audio/aac' => ['aac', 'audio'],
            'audio/wav' => ['wav', 'audio'],
            'audio/x-wav' => ['wav', 'audio'],
            'audio/ogg' => ['ogg', 'audio'],
            'audio/webm' => ['weba', 'audio'],
            'application/pdf' => ['pdf', 'document']
        ];

        $finfo = function_exists('finfo_open') ? @finfo_open(FILEINFO_MIME_TYPE) : false;
        if (!$finfo && !function_exists('mime_content_type')) {
            $respond(500, ['success' => false, 'error' => 'The server cannot validate uploaded files right now.']);
        }

        for ($i = 0, $count = count($names); $i < $count; $i++) {
            $originalName = is_string($names[$i] ?? null) ? $names[$i] : '';
            if ($originalName === '') continue;

            $error = (int)($upload['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($error !== UPLOAD_ERR_OK) {
                $cleanupUploads($mediaDir, $savedMediaFiles);
                if ($finfo) finfo_close($finfo);
                $uploadMessage = match ($error) {
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'An attachment exceeds the server upload limit. Please choose a smaller file.',
                    UPLOAD_ERR_PARTIAL => 'An attachment was only partially uploaded. Please retry.',
                    UPLOAD_ERR_NO_TMP_DIR => 'The server upload temporary folder is unavailable.',
                    UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded file. Please try again later.',
                    UPLOAD_ERR_EXTENSION => 'A server extension stopped the upload. Please try again later.',
                    default => 'An attachment could not be uploaded. Please retry.'
                };
                $respond(422, ['success' => false, 'error' => $uploadMessage]);
            }

            $tmp = (string)($upload['tmp_name'][$i] ?? '');
            $size = (int)($upload['size'][$i] ?? 0);
            if ($tmp === '' || !is_uploaded_file($tmp) || $size < 1 || $size > 10 * 1024 * 1024) {
                $cleanupUploads($mediaDir, $savedMediaFiles);
                if ($finfo) finfo_close($finfo);
                $respond(422, ['success' => false, 'error' => 'Each attachment must be valid and 10 MB or smaller.']);
            }

            $mime = $finfo ? (string)@finfo_file($finfo, $tmp) : (string)@mime_content_type($tmp);
            if (!isset($allowed[$mime])) {
                $cleanupUploads($mediaDir, $savedMediaFiles);
                if ($finfo) finfo_close($finfo);
                $respond(422, ['success' => false, 'error' => 'Unsupported attachment type. Use a photo, video, audio file or PDF.']);
            }

            [$extension, $kind] = $allowed[$mime];
            try {
                $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
            } catch (Throwable $exception) {
                $cleanupUploads($mediaDir, $savedMediaFiles);
                if ($finfo) finfo_close($finfo);
                $respond(500, ['success' => false, 'error' => 'Could not prepare the attachment safely. Please retry.']);
            }

            if (!@move_uploaded_file($tmp, $mediaDir . '/' . $fileName)) {
                $cleanupUploads($mediaDir, $savedMediaFiles);
                if ($finfo) finfo_close($finfo);
                $respond(500, ['success' => false, 'error' => 'Could not save an attachment. Please try again.']);
            }

            $savedMediaFiles[] = $fileName;
            $uploadedMedia[] = ['file' => $fileName, 'type' => $kind, 'mime' => $mime, 'size' => $size];
        }

        if ($finfo) finfo_close($finfo);
    }
}

$wish = ['name' => $name, 'message' => $message, 'pahchan' => $pahchan, 'timestamp' => $now];
if ($uploadedMedia) $wish['media'] = $uploadedMedia;

$wishesPath = __DIR__ . '/wishes.json';
$handle = @fopen($wishesPath, 'c+');
if (!$handle || !flock($handle, LOCK_EX)) {
    if (is_resource($handle)) fclose($handle);
    $cleanupUploads($mediaDir, $savedMediaFiles);
    $respond(500, ['success' => false, 'error' => 'Your wish could not be saved. Please try again.']);
}

$contents = stream_get_contents($handle);
$wishes = [];
if ($contents === false) {
    flock($handle, LOCK_UN);
    fclose($handle);
    $cleanupUploads($mediaDir, $savedMediaFiles);
    $respond(500, ['success' => false, 'error' => 'Existing wishes could not be read safely. Please try again.']);
}
if (trim($contents) !== '') {
    $decoded = json_decode($contents, true);
    if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
        flock($handle, LOCK_UN);
        fclose($handle);
        $cleanupUploads($mediaDir, $savedMediaFiles);
        $respond(500, ['success' => false, 'error' => 'The wishes data file needs administrator attention. No existing data was changed.']);
    }
    $wishes = $decoded;
}
$wishes[] = $wish;
if (count($wishes) > 5000) $wishes = array_slice($wishes, -5000);

$encoded = json_encode($wishes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
$writeOk = $encoded !== false;
if ($writeOk) {
    rewind($handle);
    $writeOk = ftruncate($handle, 0);
    $offset = 0;
    $length = strlen($encoded);
    while ($writeOk && $offset < $length) {
        $written = fwrite($handle, substr($encoded, $offset));
        if ($written === false || $written === 0) {
            $writeOk = false;
            break;
        }
        $offset += $written;
    }
    if ($writeOk) $writeOk = fflush($handle);
}
if (!$writeOk) {
    rewind($handle);
    ftruncate($handle, 0);
    if ($contents !== '') {
        $offset = 0;
        $originalLength = strlen($contents);
        while ($offset < $originalLength) {
            $written = fwrite($handle, substr($contents, $offset));
            if ($written === false || $written === 0) break;
            $offset += $written;
        }
        fflush($handle);
    }
    flock($handle, LOCK_UN);
    fclose($handle);
    $cleanupUploads($mediaDir, $savedMediaFiles);
    $respond(500, ['success' => false, 'error' => 'Your wish could not be saved. Existing wishes were preserved.']);
}
flock($handle, LOCK_UN);
fclose($handle);

$_SESSION['birthday_last_wish_at'] = $now;
$configPath = __DIR__ . '/experience.json';
$config = is_file($configPath) ? json_decode((string)@file_get_contents($configPath), true) : [];
if (!is_array($config)) $config = [];
$notifications = is_array($config['notifications'] ?? null) ? $config['notifications'] : [];

// Return success as soon as the wish and its media references are safely persisted.
// Finish the HTTP response before the optional email notification on PHP-FPM hosts.
$successJson = json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
echo $successJson;
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) @ob_end_flush();
    }
    @flush();
}

// Email is a secondary notification; it must never undo or invalidate a saved wish.
$to = trim((string)($notifications['to'] ?? ''));
$fromEmail = trim((string)($notifications['fromEmail'] ?? ''));
$fromName = trim((string)($notifications['fromName'] ?? 'Birthday Bot')) ?: 'Birthday Bot';
if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
    $escapeEmailHtml = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeName = $escapeEmailHtml($name);
    $safeMessage = nl2br($escapeEmailHtml($message), false);
    $relationLabels = [
        'Dada-Dadi' => 'Dada/Dadi',
        'Nana-Nani' => 'Nana/Nani',
        'Mama-Mami' => 'Mama/Mami',
        'Chacha-Chachi' => 'Chacha/Chachi',
        'Bua-Fufa' => 'Bua/Fufa'
    ];
    $displayPahchan = $relationLabels[$pahchan] ?? $pahchan;
    $safePahchan = $displayPahchan !== '' ? $escapeEmailHtml($displayPahchan) : 'Not shared';
    $subjectName = trim(preg_replace('/[\r\n]+/', ' ', $name));
    $subjectText = "New Birthday Wish from {$subjectName}!";
    $subject = function_exists('mb_encode_mimeheader')
        ? mb_encode_mimeheader($subjectText, 'UTF-8', 'B', "\r\n")
        : '=?UTF-8?B?' . base64_encode($subjectText) . '?=';

    $shareUrl = trim((string)($config['experience']['shareUrl'] ?? ''));
    if ($shareUrl === '' || !filter_var($shareUrl, FILTER_VALIDATE_URL)) {
        $shareUrl = 'https://www.docsansar.com/birthday-wish/';
    }
    $shareParts = parse_url($shareUrl);
    $shareScheme = strtolower((string)($shareParts['scheme'] ?? ''));
    $shareHost = (string)($shareParts['host'] ?? '');
    $sharePath = (string)($shareParts['path'] ?? '/');
    $mediaBaseUrl = '';
    if (in_array($shareScheme, ['http', 'https'], true) && $shareHost !== '') {
        $basePath = rtrim(str_replace('\\', '/', dirname($sharePath)), '/.');
        $mediaBaseUrl = $shareScheme . '://' . $shareHost . ($basePath !== '' ? $basePath : '') . '/wish-media/';
    }

    $mediaHtml = '';
    $mediaTextLines = [];
    $mediaItems = is_array($wish['media'] ?? null) ? array_slice($wish['media'], 0, 3) : [];
    $mediaCount = 0;
    foreach ($mediaItems as $item) {
        if (!is_array($item)) continue;
        $file = basename((string)($item['file'] ?? ''));
        if (!preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|webp|gif|mp4|webm|mov|mp3|m4a|wav|ogg|weba|aac|pdf)$/i', $file)) continue;
        $extension = strtolower((string)pathinfo($file, PATHINFO_EXTENSION));
        $kind = (string)($item['type'] ?? '');
        if ($kind === '') {
            $kind = in_array($extension, ['jpg','jpeg','png','webp','gif'], true) ? 'image'
                : (in_array($extension, ['mp4','webm','mov'], true) ? 'video'
                : (in_array($extension, ['mp3','m4a','wav','ogg','weba','aac'], true) ? 'audio' : 'document'));
        }
        $labels = ['image' => 'Photo', 'video' => 'Video', 'audio' => 'Audio', 'document' => 'PDF document'];
        $label = $labels[$kind] ?? 'Attachment';
        $sizeBytes = max(0, (int)($item['size'] ?? 0));
        $sizeLabel = $sizeBytes > 0 ? ($sizeBytes >= 1048576 ? number_format($sizeBytes / 1048576, 1) . ' MB' : number_format($sizeBytes / 1024, 0) . ' KB') : '';
        $mediaUrl = $mediaBaseUrl !== '' ? $mediaBaseUrl . rawurlencode($file) : '';
        if ($mediaUrl === '') continue;
        $safeMediaUrl = $escapeEmailHtml($mediaUrl);
        $mediaCount++;
        $mediaTextLines[] = $label . ': ' . $mediaUrl;

        if ($kind === 'image') {
            $mediaHtml .= '<tr><td style="padding:0 0 14px;"><a href="' . $safeMediaUrl . '" target="_blank" style="text-decoration:none;"><img src="' . $safeMediaUrl . '" alt="Birthday wish photo" width="500" style="display:block;width:100%;max-width:500px;height:auto;border:0;border-radius:12px;background:#fff0f4;"></a><div style="padding-top:6px;font-size:11px;color:#9b8992;">Photo ' . $mediaCount . ($sizeLabel !== '' ? ' · ' . $escapeEmailHtml($sizeLabel) : '') . '</div></td></tr>';
        } else {
            $symbol = $kind === 'video' ? '▶' : ($kind === 'audio' ? '♫' : 'PDF');
            $description = $kind === 'video' ? 'Open video in your browser' : ($kind === 'audio' ? 'Listen to the audio in your browser' : 'View or download the PDF');
            $mediaHtml .= '<tr><td style="padding:0 0 10px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#fff8fb;border:1px solid #f2dce5;border-radius:12px;"><tr><td width="54" align="center" style="padding:13px 5px;font-size:22px;color:#c45e83;font-weight:bold;">' . $symbol . '</td><td style="padding:12px 10px 12px 4px;"><div style="font-size:14px;font-weight:bold;color:#68475a;">' . $label . ($sizeLabel !== '' ? ' · ' . $escapeEmailHtml($sizeLabel) : '') . '</div><div style="font-size:12px;color:#8e7884;padding-top:4px;">' . $description . '</div><a href="' . $safeMediaUrl . '" target="_blank" style="display:inline-block;margin-top:7px;color:#b64e76;font-size:12px;font-weight:bold;">Open attachment →</a></td></tr></table></td></tr>';
        }
    }
    if ($mediaCount > 0) {
        $mediaHtml = '<div style="font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#a06b7f;font-weight:bold;margin-bottom:12px;">Attached memories (' . $mediaCount . ')</div><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' . $mediaHtml . '</table>';
    } else {
        $mediaHtml = '<div style="font-size:12px;color:#9b8992;">No media was attached to this wish.</div>';
    }

    $templatePath = __DIR__ . '/email-template.html';
    $template = is_file($templatePath) ? @file_get_contents($templatePath) : false;
    if ($template !== false) {
        $timestampLabel = date('d M Y, h:i A', $now) . ' IST';
        $preheader = $name . ' has sent a birthday wish.';
        $template = str_replace(
            ['{{NAME}}', '{{MESSAGE}}', '{{TIMESTAMP}}', '{{PAHCHAN}}', '{{SHARE_URL}}', '{{MEDIA_SECTION}}', '{{PREHEADER}}', '{{YEAR}}'],
            [$safeName, $safeMessage, $escapeEmailHtml($timestampLabel), $safePahchan, $escapeEmailHtml($shareUrl), $mediaHtml, $escapeEmailHtml($preheader), date('Y')],
            $template
        );

        $plainMessage = "A new birthday wish has arrived!\n\nFrom: {$name}\nRelationship: " . ($displayPahchan !== '' ? $displayPahchan : 'Not shared') . "\n\n{$message}\n\nReceived: {$timestampLabel}\n";
        if ($mediaTextLines) $plainMessage .= "\nAttachments:\n- " . implode("\n- ", $mediaTextLines) . "\n";
        else $plainMessage .= "\nNo media was attached.\n";
        $plainMessage .= "\nOpen birthday experience: {$shareUrl}\n";
        $boundary = '=_BirthdayWish_' . bin2hex(random_bytes(12));
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . "\r\n";
        $senderAddress = filter_var($fromEmail, FILTER_VALIDATE_EMAIL) ? $fromEmail : $to;
        $headers .= 'From: ' . preg_replace('/[\r\n]+/', ' ', $fromName) . " <{$senderAddress}>\r\n";
        foreach (['cc', 'bcc'] as $type) {
            $list = trim((string)($notifications[$type] ?? ''));
            if ($list !== '') {
                $valid = array_filter(array_map('trim', preg_split('/[,;]+/', $list)), static fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
                if ($valid) $headers .= strtoupper($type) . ': ' . implode(', ', $valid) . "\r\n";
            }
        }
        $body = '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($plainMessage)) . "\r\n";
        $body .= '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($template)) . "\r\n";
        $body .= '--' . $boundary . "--\r\n";
        @mail($to, $subject, $body, $headers);
    }
}
