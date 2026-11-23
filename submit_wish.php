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
    $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    $safePahchan = $pahchan !== '' ? htmlspecialchars($pahchan, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : 'Not shared';
    $subjectName = preg_replace('/[\r\n]+/', ' ', $name);
    $subject = "New Birthday Wish from {$subjectName}!";
    $templatePath = __DIR__ . '/email-template.html';
    $template = is_file($templatePath) ? @file_get_contents($templatePath) : false;
    if ($template !== false) {
        $shareUrl = trim((string)($config['experience']['shareUrl'] ?? ''));
        if ($shareUrl === '') $shareUrl = 'https://www.docsansar.com/birthday';
        $template = str_replace(
            ['{{NAME}}', '{{MESSAGE}}', '{{TIMESTAMP}}', '{{PAHCHAN}}', '{{SHARE_URL}}'],
            [$safeName, $safeMessage, date('d M Y, h:i A', $now), $safePahchan, htmlspecialchars($shareUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')],
            $template
        );
        $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
        $senderAddress = filter_var($fromEmail, FILTER_VALIDATE_EMAIL) ? $fromEmail : $to;
        $headers .= 'From: ' . preg_replace('/[\r\n]+/', ' ', $fromName) . " <{$senderAddress}>\r\n";
        foreach (['cc', 'bcc'] as $type) {
            $list = trim((string)($notifications[$type] ?? ''));
            if ($list !== '') {
                $valid = array_filter(array_map('trim', preg_split('/[,;]+/', $list)), static fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
                if ($valid) $headers .= strtoupper($type) . ': ' . implode(', ', $valid) . "\r\n";
            }
        }
        @mail($to, $subject, $template, $headers);
    }
}
