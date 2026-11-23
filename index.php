<?php

ini_set('log_errors', 1);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'] . '/auxiliaries/errors.log');
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/bootstrap.php";

update_user_activity();
$featuredImages = glob('featured/*.{jpg,jpeg,png}', GLOB_BRACE) ?: [];
$mediaIndexPath = __DIR__ . '/birthday-media/.media-index.json';
$birthdayMedia = is_file($mediaIndexPath) ? json_decode((string)file_get_contents($mediaIndexPath), true) : [];
if (!is_array($birthdayMedia)) $birthdayMedia = [];
$wishesFile = 'wishes.json';
if (!file_exists($wishesFile)) file_put_contents($wishesFile, '[]');

$experienceConfigPath = __DIR__ . '/experience.json';
$exampleConfigPath = __DIR__ . '/experience.example.json';
$configSource = is_file($experienceConfigPath) ? $experienceConfigPath : $exampleConfigPath;
$experienceConfig = is_file($configSource) ? json_decode((string) file_get_contents($configSource), true) : [];
if (!is_array($experienceConfig)) $experienceConfig = [];
$personConfig = is_array($experienceConfig['person'] ?? null) ? $experienceConfig['person'] : [];
$name = trim((string) ($personConfig['name'] ?? 'Someone special')) ?: 'Someone special';
$displayName = trim((string) ($personConfig['nickname'] ?? '')) ?: $name;
$pronoun = in_array(($personConfig['pronoun'] ?? 'they'), ['he','she','they'], true) ? $personConfig['pronoun'] : 'they';
$pronouns = ['he'=>['subject'=>'he','object'=>'him','possessive'=>'his'],'she'=>['subject'=>'she','object'=>'her','possessive'=>'her'],'they'=>['subject'=>'they','object'=>'them','possessive'=>'their']];
$personPronouns = $pronouns[$pronoun];
$birthdayDate = trim((string)($personConfig['birthday'] ?? ''));
$birthdayAge = null;
if ($birthdayDate !== '') { try { $birth = new DateTimeImmutable($birthdayDate); $today = new DateTimeImmutable('today'); if ($birth <= $today) $birthdayAge = $birth->diff($today)->y; } catch (Throwable $e) { $birthdayAge = null; } }
$birthdayLabel = $birthdayAge !== null ? $birthdayAge . ' saal ka ho gaya!' : 'Aaj ka din sirf Vaibhav ke naam!';
$experienceSettings = is_array($experienceConfig['experience'] ?? null) ? $experienceConfig['experience'] : [];
$themeSettings = is_array($experienceConfig['theme'] ?? null) ? $experienceConfig['theme'] : [];
$themePreset = in_array(($themeSettings['preset'] ?? 'rose'), ['rose','sage','lavender','sunset','sunshine'], true) ? $themeSettings['preset'] : 'sunshine';
$themeAccent = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($themeSettings['accent'] ?? '')) ? $themeSettings['accent'] : '#ffb84d';
$wishBoardHeading = trim((string)($experienceSettings['wishBoardHeading'] ?? '')) ?: 'A little love from everyone';
$namePlaceholder = trim((string)($experienceSettings['namePlaceholder'] ?? '')) ?: 'Your name';
$relationshipPlaceholder = trim((string)($experienceSettings['relationshipPlaceholder'] ?? '')) ?: 'How do you know them? (optional)';
$messagePlaceholder = trim((string)($experienceSettings['messagePlaceholder'] ?? '')) ?: 'Write a warm wish or a favourite memory…';
$scrollHint = trim((string)($experienceSettings['scrollHint'] ?? '')) ?: 'Stay a little, leave a wish, and enjoy the memories.';
$submitLabel = trim((string)($experienceSettings['submitLabel'] ?? '')) ?: 'Send your wish';
$metaDescription = trim((string)($experienceSettings['metaDescription'] ?? '')) ?: "Celebrate {$name}'s special day. Share a personal wish and a favourite memory.";
$publicBaseUrl = $protocol . $host . rtrim($current_directory, '/');
$configuredOgImage = trim((string)($experienceSettings['ogImage'] ?? ''));
$shareImage = $publicBaseUrl . '/favicon.ico';
if ($configuredOgImage !== '') {
    $ogParts = parse_url($configuredOgImage);
    if (filter_var($configuredOgImage, FILTER_VALIDATE_URL) && strtolower((string)($ogParts['scheme'] ?? '')) === 'https') {
        $shareImage = $configuredOgImage;
    } elseif (preg_match('#^birthday-media/[a-f0-9]{32}\\.(?:jpg|jpeg|png|webp|gif)$#i', $configuredOgImage) && is_file(__DIR__ . '/' . $configuredOgImage)) {
        $shareImage = $publicBaseUrl . '/' . $configuredOgImage;
    }
}
if ($shareImage === $publicBaseUrl . '/favicon.ico') {
    foreach ($birthdayMedia as $memoryForOg) {
        if (!is_array($memoryForOg) || ($memoryForOg['type'] ?? 'image') === 'video' || empty($memoryForOg['file'])) continue;
        $firstMemory = 'birthday-media/' . basename((string)$memoryForOg['file']);
        if (preg_match('#^birthday-media/[a-f0-9]{32}\\.(?:jpg|jpeg|png|webp|gif)$#i', $firstMemory) && is_file(__DIR__ . '/' . $firstMemory)) {
            $shareImage = $publicBaseUrl . '/' . $firstMemory;
            break;
        }
    }
}
$shareUrl = trim((string)($experienceSettings['shareUrl'] ?? '')) ?: 'https://www.docsansar.com/birthday';
$heroTitle = trim((string)($experienceSettings['title'] ?? '')) ?: "Happy Birthday {$displayName}!";
$heroSubtitle = trim((string)($experienceSettings['subtitle'] ?? '')) ?: 'May your year ahead be full of success, peace, and unforgettable moments.';
$modules = is_array($experienceConfig['modules'] ?? null) ? $experienceConfig['modules'] : [];
$celebrationSettings = is_array($experienceConfig['celebration'] ?? null) ? $experienceConfig['celebration'] : [];
$confettiEnabled = ($celebrationSettings['confetti'] ?? true) !== false;
$floatingDecorEnabled = ($celebrationSettings['floatingDecor'] ?? true) !== false;
$celebrationTrailEnabled = ($celebrationSettings['celebrationTrail'] ?? true) !== false;
$controlsAutoHideSeconds = max(3, min(15, (int)($celebrationSettings['controlsAutoHideSeconds'] ?? 5)));
$showLetter = ($modules['letter'] ?? true) !== false;
$showCake = ($modules['cake'] ?? true) !== false;
$showFinal = ($modules['final'] ?? true) !== false;
$musicEnabled = ($modules['music'] ?? true) !== false;
$letterHeading = trim((string)($experienceSettings['letterHeading'] ?? '')) ?: 'A little note for you';
$letterMessage = trim((string)($experienceSettings['letterMessage'] ?? '')) ?: 'Some people make ordinary days feel special. Today is a chance to celebrate everything that makes you wonderful.';
$letterButton = trim((string)($experienceSettings['letterButton'] ?? '')) ?: 'Open your little letter';
$cakeHeading = trim((string)($experienceSettings['cakeHeading'] ?? '')) ?: 'Make a little wish';
$cakeMessage = trim((string)($experienceSettings['cakeMessage'] ?? '')) ?: 'Take a breath, make a wish, and keep a little room for magic.';
$cakeButton = trim((string)($experienceSettings['cakeButton'] ?? '')) ?: 'Blow out the candles';
$finalHeading = trim((string)($experienceSettings['finalHeading'] ?? '')) ?: 'Here is to everything ahead';
$finalMessage = trim((string)($experienceSettings['finalMessage'] ?? '')) ?: 'May the next chapter bring kind people, beautiful surprises and moments worth remembering.';
$customMusic = trim((string)($experienceSettings['musicUrl'] ?? ''));
$musicSource = '';
if ($customMusic !== '') {
    $musicParts = parse_url($customMusic);
    if (filter_var($customMusic, FILTER_VALIDATE_URL) && in_array(strtolower((string)($musicParts['scheme'] ?? '')), ['https'], true)) $musicSource = $customMusic;
}
if ($musicSource === '') $musicSource = 'happy-birthday-song.mp3';
$wishFormHeading = trim((string)($experienceSettings['formHeading'] ?? '')) ?: 'Leave a little happiness';
$wishFormDescription = trim((string)($experienceSettings['formDescription'] ?? '')) ?: 'Share a note, a memory or a wish. It will mean more than you know.';
$shareMessage = "🎈 Vaibhav ka 2nd Birthday! 🎂\n\nHamare chhote Kittu ko birthday wish bhejiye! ❤️\nApni pyari si wish yahan bhejein 👉 {$shareUrl}\n\nPhotos dekhein, wish bhejein aur celebration ka hissa banein! 🎉";
$escapeHtml = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="hi-Latn">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<!-- Primary Meta Tags -->
<title>Happy Birthday <?= $escapeHtml($name); ?> 🎉 | Send Your Wishes!</title>
<meta name="title" content="Happy Birthday <?= $escapeHtml($name); ?> 🎉 | Send Your Wishes!">
<meta name="description" content="<?= $escapeHtml($metaDescription); ?>">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?= $escapeHtml($shareUrl); ?>">
<meta property="og:title" content="Happy Birthday <?= $escapeHtml($name); ?> 🎉 | Send Your Wishes!">
<meta property="og:description" content="<?= $escapeHtml($metaDescription); ?>">
<meta property="og:image" content="<?= $escapeHtml($shareImage); ?>">
<meta property="og:image:alt" content="<?= $escapeHtml($name); ?> birthday celebration">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="<?= $escapeHtml($shareUrl); ?>">
<meta name="twitter:title" content="Happy Birthday <?= $escapeHtml($name); ?> 🎉 | Send Your Wishes!">
<meta name="twitter:description" content="<?= $escapeHtml($metaDescription); ?>">
<meta name="twitter:image" content="<?= $escapeHtml($shareImage); ?>">



  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" />
  
  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
  <style>
  @import url('https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@100..900&display=swap');
    body {
      background: linear-gradient(to bottom, #fff0f5, #fefefe);
      
      font-family:"Lexend Deca", sans-serif;
      overflow-x: hidden;
    }
    .lazy { opacity: 0; transition: opacity 0.5s ease-in; }
    .lazy.loaded { opacity: 1; }
    .heading-glow:hover {
      text-shadow: 0 0 8px rgba(255, 105, 180, 0.7);
    }
    .confetti {
      position: fixed;
      top: 0; left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 9999;
    }
      small{
          display: block;
          text-align: center;
          padding: 2px;
      }
      img, video {
  user-select: none;
  -webkit-user-drag: none;
}

  </style>
<style>
:root{--kid-ink:#51433c;--kid-muted:#81736a;--kid-accent:#ffb84d;--kid-coral:#ff806f}
body.birthday-public{margin:0!important;background:linear-gradient(180deg,#fff8e8,#fffdf8 45%,#fff4e8)!important;color:var(--kid-ink)!important;font-family:system-ui,-apple-system,'Segoe UI',sans-serif!important;overflow-x:hidden}
.birthday-hero{text-align:center;padding:clamp(30px,7vw,72px) 18px 42px;background:linear-gradient(145deg,#ffe5a9,#fff0d6 48%,#dff4fb);border-radius:0 0 38px 38px;position:relative;overflow:hidden}.birthday-hero__top{font-size:clamp(13px,2.4vw,17px);font-weight:800;color:#98672e}.birthday-hero__toys{font-size:clamp(25px,5vw,42px);letter-spacing:8px;margin:16px 0 8px}
.birthday-hero h1{font-size:clamp(36px,8vw,72px)!important;line-height:1.12;font-weight:900;color:#e66d55!important;margin:8px auto 12px;max-width:900px;text-shadow:0 3px #fff}.birthday-hero p{font-size:clamp(16px,3vw,21px);line-height:1.6;color:#66574b;max-width:660px;margin:0 auto}.birthday-age{display:inline-flex;margin:18px auto 0;padding:10px 18px;border-radius:999px;background:#fff;color:#a65e37;font-weight:800;box-shadow:0 5px 16px #b7793420}.birthday-hero__button{display:table;margin:22px auto 0;padding:15px 24px;border-radius:999px;background:#ff806f;color:#fff!important;text-decoration:none;font-size:17px;font-weight:850;box-shadow:0 7px #e56d5f;min-height:52px}.birthday-hero__bottom{font-size:24px;letter-spacing:9px;margin-top:24px}
.birthday-gallery{padding:30px 16px 18px}.birthday-all-memories-wrap{max-width:560px;margin:-5px auto 20px;padding:0 12px}.birthday-all-memories-card{position:relative;display:flex;align-items:flex-end;min-height:148px;overflow:hidden;border:1px solid #edbd78;border-radius:22px;background:linear-gradient(135deg,#f8dfb8,#ffeecf);color:#fff!important;text-decoration:none;box-shadow:0 9px 24px #9a704022;isolation:isolate;transition:transform .18s ease,box-shadow .18s ease}.birthday-all-memories-card>img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center;z-index:-2}.birthday-all-memories-card__placeholder{position:absolute;inset:0;display:grid;place-items:center;font-size:58px;color:#a87543;z-index:-2}.birthday-all-memories-card__shade{position:absolute;inset:0;background:linear-gradient(90deg,#382419b8 0%,#3824195c 58%,#38241912 100%),linear-gradient(0deg,#38241965,transparent 65%);z-index:-1}.birthday-all-memories-card__content{display:grid;gap:4px;padding:19px 58px 18px 20px;text-shadow:0 2px 8px #38241955}.birthday-all-memories-card__content strong{font-size:19px;font-weight:900}.birthday-all-memories-card__content small{font-size:12px;font-weight:650;opacity:.95}.birthday-all-memories-card__arrow{position:absolute;right:16px;top:16px;width:36px;height:36px;display:grid;place-items:center;border-radius:50%;background:#fffdf0e8;color:#85542d;font-size:20px;font-weight:900}.birthday-all-memories-card:hover,.birthday-all-memories-card:focus-visible{transform:translateY(-2px);box-shadow:0 13px 28px #9a704033;outline:3px solid #ffb84d77;outline-offset:3px}@media(prefers-reduced-motion:reduce){.birthday-all-memories-card{transition:none}}.birthday-section-heading{text-align:center;margin:0 auto 20px}.birthday-section-heading>span{font-size:30px}.birthday-section-heading h2{font-size:clamp(25px,5vw,34px);font-weight:850;color:#80543f;margin:5px 0}.birthday-section-heading p{margin:0;color:#81736a}.birthday-gallery__frame{width:100%;max-width:560px;height:min(68vh,620px);height:clamp(300px,68svh,620px);min-height:280px;margin:0 auto;border-radius:28px;overflow:hidden;background:#f7e8d3;box-shadow:0 12px 35px #76533318;contain:layout paint}.birthday-gallery__frame .swiper-wrapper,.birthday-gallery__frame .swiper-slide{height:100%}.birthday-gallery__frame .swiper-slide{position:relative;display:flex;align-items:center;justify-content:center;background:#f7e8d3}.birthday-gallery__frame img,.birthday-gallery__frame video{display:block;width:100%;height:100%;max-width:100%;max-height:100%;object-fit:contain;object-position:center;background:#f7e8d3}.birthday-gallery__empty{max-width:560px;margin:auto;padding:40px 18px;border:2px dashed #f0d5ae;border-radius:26px;text-align:center;background:#fffdf8}.birthday-gallery__empty span{display:block;font-size:60px}.birthday-gallery__empty strong,.birthday-gallery__empty small{display:block}.birthday-gallery__empty strong{font-size:17px;color:#76563e}.birthday-gallery__empty small{margin-top:7px;color:#998777}
.birthday-surprise-section{padding:22px 15px}.surprise-card{max-width:650px;margin:auto;padding:28px 20px;border:1px solid #f1dfc7;border-radius:26px;background:#fffdf8;color:#51433c;box-shadow:0 10px 28px #8b643012;text-align:center}.surprise-card h2{font-size:clamp(24px,5vw,34px);font-weight:850;color:#80543f}.surprise-card p{color:#76685d;line-height:1.7}.surprise-kicker{color:#d47b53;font-size:12px;font-weight:850}.surprise-button{background:#ffb84d;color:#5d3d20;border-radius:999px;padding:14px 20px;min-height:48px;font-weight:850}.cake-illustration{font-size:72px}
.wish-invite-section{padding:30px 15px 44px;background:linear-gradient(180deg,#fff5e7,#fffdf8)}.wish-invite-card{max-width:620px;margin:auto;padding:clamp(20px,5vw,36px);border-radius:30px;background:#fff;border:2px solid #f3dfc4;box-shadow:0 16px 42px #9a704014}.wish-invite-kicker{text-align:center;color:#d17a4f;font-weight:850}.wish-invite-card h2{text-align:center;font-size:clamp(26px,5vw,38px);font-weight:900;color:#82543d;margin:8px 0}.wish-invite-description{text-align:center;color:#83756b;line-height:1.6;margin:0 auto 24px;max-width:480px}.wish-field-label{display:block;color:#655348;font-weight:850;font-size:15px;margin:18px 0 9px}.wish-field-label span{font-size:12px;color:#9a8c81;font-weight:500}.wish-input{display:block;width:100%;min-height:54px;padding:15px 17px;border:2px solid #f0dfca;border-radius:16px;background:#fffdf9;color:#4e4038;font-size:17px;outline:none;box-shadow:none}.wish-input:focus{border-color:#ffb84d;box-shadow:0 0 0 4px #ffb84d2b}.wish-message-input{min-height:116px;resize:vertical;line-height:1.55}
.wish-relationship-choices,.wish-suggestions{display:flex;flex-wrap:wrap;gap:9px}.wish-choice,.wish-suggestion{min-height:44px;padding:10px 14px;border:1.5px solid #f0dfca;border-radius:999px;background:#fffaf1;color:#765c48;font-size:14px;font-weight:750}.wish-choice.selected,.wish-suggestion.selected{background:#ffedc8;border-color:#e6a844;color:#69451e;box-shadow:0 0 0 2px #ffe4ad}.wish-suggestions{display:grid;grid-template-columns:1fr 1fr}.wish-suggestion{text-align:left;border-radius:15px;min-height:58px;line-height:1.35}.wish-privacy-note{text-align:center;color:#95867b;font-size:13px;line-height:1.5;margin:14px 0}.wish-send-button{display:block;width:100%;min-height:58px;padding:14px 20px;border-radius:17px;background:#ff806f;color:#fff;font-size:18px;font-weight:900;box-shadow:0 5px #e56d5f}.wish-send-button:disabled{opacity:.65;box-shadow:none}.wish-feedback{text-align:center;min-height:22px;margin:14px 0 0;color:#8a5b32;font-weight:750}
.birthday-public #wishBoard{gap:14px}.birthday-public #wishBoard article{background:#fffdf8!important;border:1px solid #f1dfc7;box-shadow:0 8px 22px #805b3010!important;border-radius:20px!important}.birthday-public #wishBoard article p{color:#64554a!important}.birthday-public footer{background:#fff3df!important;color:#806d5d!important;border-color:#f1dfc7!important}.birthday-public .btn{min-height:44px}.birthday-public .swiper-pagination-bullet-active{background:#ff806f!important}
@media(max-width:520px){.birthday-hero{padding-top:34px}.birthday-gallery__frame{height:min(64vh,560px);height:clamp(260px,64svh,560px);min-height:260px}.wish-suggestions{grid-template-columns:1fr}.wish-invite-card{border-radius:23px}.birthday-hero__toys{letter-spacing:3px}.birthday-public .mt-6.flex{padding:0 12px 18px}.birthday-public .btn{flex:1 1 42%;font-size:13px}}
.wish-invite-card{max-width:650px!important;padding:clamp(18px,3vw,28px)!important}.wish-invite-description{margin-bottom:14px!important}.wish-field-label{font-size:13px!important;margin:10px 0 6px!important}.wish-input{min-height:48px!important;padding:11px 14px!important;font-size:15px!important}.wish-message-input{min-height:78px!important;resize:vertical}.wish-name-hint{display:block;margin:5px 2px 8px;color:#a16f45;font-size:11px}.wish-extra-options{margin:10px 0;padding:10px 13px;border:1px solid #f0e1d0;border-radius:15px;background:#fffaf2}.wish-extra-options summary{cursor:pointer;color:#8b654b;font-size:12px;font-weight:800}.wish-extra-options[open] summary{margin-bottom:10px}.wish-extra-options .wish-choice{min-height:36px;padding:7px 11px;font-size:12px}.wish-suggestions{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:7px!important}.wish-suggestion{min-height:44px!important;padding:9px 11px!important;font-size:12px!important}.wish-attachment-box{padding:12px;border:1px dashed #e8d4bd;border-radius:16px;background:#fffaf2}.wish-attachment-input{display:block;width:100%;font-size:12px;color:#766254}.wish-attachment-box small{display:block;margin-top:6px;color:#958477;font-size:10px}.wish-attachment-list{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}.wish-attachment-list span{padding:5px 9px;border-radius:999px;background:#f5eadc;color:#806044;font-size:10px}.wish-send-button{min-height:52px!important;font-size:16px!important}.birthday-audio-memory{width:100%;min-height:180px;display:flex;flex-direction:column;justify-content:center;gap:14px;padding:22px;background:linear-gradient(145deg,#fff0d7,#e8f3e8);color:#76543d;font-weight:850}.birthday-audio-memory audio{width:100%;max-width:100%}.wish-board-attachments{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}.wish-board-attachments img,.wish-board-attachments video{width:min(100%,230px);max-height:240px;object-fit:contain;border-radius:13px;background:#f5eadc}.wish-board-attachments audio{width:min(100%,280px)}.wish-board-attachments a{display:inline-block;padding:8px 12px;border-radius:999px;background:#fff0d9;color:#80543f;font-size:12px;font-weight:800}@media(max-width:520px){.wish-invite-card{padding:18px!important}.wish-suggestions{grid-template-columns:1fr!important}.wish-attachment-box{padding:10px}}
  .birthday-swal-popup{border-radius:26px!important;font-family:system-ui,-apple-system,'Segoe UI',sans-serif!important;padding:1.5rem!important}.birthday-swal-popup .swal2-title{color:#80543f;font-weight:900}.birthday-swal-popup .swal2-html-container{color:#76685d;line-height:1.65}.birthday-swal-popup .swal2-confirm,.birthday-swal-popup .swal2-cancel{border-radius:14px!important;font-weight:800!important;padding:12px 20px!important}
</style>

<style id="vaibhav-memory-lightbox-fix">
/* The page positions every direct body child relatively; restore GLightbox's viewport overlay positioning. */
body.birthday-public>.glightbox-container{position:fixed!important;inset:0!important;z-index:30000!important;width:100vw!important;height:100dvh!important}
body.birthday-public>.glightbox-container .gcontainer{height:100%!important}
body.birthday-public>.glightbox-container .gslide-media{max-width:92vw!important;max-height:84vh!important}
body.birthday-public>.glightbox-container .gslide-image img{max-width:92vw!important;max-height:84vh!important;width:auto!important;height:auto!important;object-fit:contain!important}
body.birthday-public>.glightbox-container .gclose,
body.birthday-public>.glightbox-container .gnext,
body.birthday-public>.glightbox-container .gprev{z-index:30002!important;opacity:1!important;visibility:visible!important}
@media(max-width:600px){body.birthday-public>.glightbox-container .gslide-media{max-width:96vw!important;max-height:78vh!important}body.birthday-public>.glightbox-container .gslide-image img{max-width:96vw!important;max-height:78vh!important}}
</style>
<style id="vaibhav-memory-lightbox">
.birthday-gallery__frame .memory-lightbox-link{position:relative;display:flex;align-items:center;justify-content:center;width:100%;height:100%;min-width:0;min-height:0;cursor:zoom-in}
.birthday-gallery__frame .memory-lightbox-link img,.birthday-gallery__frame .memory-lightbox-link video{width:100%;height:100%;object-fit:contain}
.memory-media-play{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);display:grid;place-items:center;width:68px;height:68px;border-radius:50%;background:#ffffffdf;color:#c85e4f;font-size:27px;box-shadow:0 8px 30px #0002;pointer-events:none}
.memory-media-icon{font-size:clamp(42px,9vw,72px);line-height:1}
.memory-media-open-hint{font-size:13px;opacity:.75}
.birthday-gallery__frame .memory-media-trigger:not(:has(video)){flex-direction:column;gap:12px;padding:22px;background:linear-gradient(145deg,#fff0d7,#e8f3e8);color:#76543d;font-weight:850;text-decoration:none}
.lightbox-inline-media{display:none!important}
.lightbox-inline-card{width:min(560px,88vw);padding:clamp(22px,5vw,42px);border-radius:22px;background:#fffaf2;color:#76543d;display:flex;flex-direction:column;align-items:center;gap:18px;text-align:center}
.lightbox-inline-card audio{width:100%;max-width:100%}
.lightbox-inline-card iframe{width:min(850px,85vw);height:min(78vh,850px);border:0;background:#fff}
.wish-board-attachments{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px}
.wish-board-attachments .wish-media-trigger{position:relative;display:flex;align-items:center;justify-content:center;min-width:112px;min-height:92px;max-width:220px;padding:10px;border-radius:14px;background:#fff3e2;color:#80543f;text-decoration:none;overflow:hidden}
.wish-board-attachments .wish-media-trigger img,.wish-board-attachments .wish-media-trigger video{display:block;width:100%;max-height:190px;object-fit:contain;border-radius:10px}
.wish-board-attachments .wish-media-trigger .wish-media-label{font-size:12px;font-weight:800}
.wish-board-attachments .wish-media-trigger:has(img),.wish-board-attachments .wish-media-trigger:has(video){padding:0;background:#f5eadc}
.wish-board-attachments .wish-media-trigger:has(img) img{max-height:220px}
</style>
<style id="vaibhav-premium-experience">
:root{--lux-ink:#49372f;--lux-muted:#88766b;--lux-cream:#fffaf0;--lux-peach:#ffe2c8;--lux-coral:#f47e70;--lux-gold:#f4bd61;--lux-shadow:0 24px 70px rgba(105,70,46,.12);scroll-behavior:smooth}
body.birthday-public{background:radial-gradient(ellipse at 8% 4%,#fff0d8 0,transparent 34%),radial-gradient(ellipse at 92% 24%,#e5f5f0 0,transparent 32%),linear-gradient(180deg,#fffaf2 0%,#fffdf9 48%,#fff3e8 100%)!important;color:var(--lux-ink)!important}
body.birthday-public:before{content:"";position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.2;background-image:radial-gradient(#d9a77d 1px,transparent 1px);background-size:26px 26px;mask-image:linear-gradient(to bottom,black,transparent 70%)}
.birthday-public>*{position:relative}
.birthday-public>#confetti-canvas{position:fixed!important;inset:0!important;pointer-events:none!important;z-index:9999!important}
.birthday-hero{isolation:isolate;min-height:590px;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:clamp(65px,10vw,118px) 20px 76px!important;border-radius:0 0 58px 58px!important;background:linear-gradient(135deg,#ffe9bd 0%,#fff0d9 42%,#dff3ee 100%)!important;box-shadow:0 28px 80px rgba(129,91,51,.10);overflow:hidden}
.birthday-hero:before,.birthday-hero:after{content:"";position:absolute;z-index:-1;border-radius:50%;filter:blur(1px);pointer-events:none}
.birthday-hero:before{width:320px;height:320px;left:-110px;top:-100px;background:radial-gradient(circle,#fff9df 0 38%,transparent 70%);animation:luxFloat 9s ease-in-out infinite}
.birthday-hero:after{width:390px;height:390px;right:-150px;bottom:-180px;background:radial-gradient(circle,#c9e9dc 0 35%,transparent 72%);animation:luxFloat 11s ease-in-out infinite reverse}
.birthday-hero__top{padding:10px 18px;border:1px solid rgba(180,132,75,.18);border-radius:999px;background:#ffffff9c;backdrop-filter:blur(12px);box-shadow:0 8px 28px #986b3212;letter-spacing:.04em;animation:luxRise .8s both}
.birthday-hero__toys{font-size:clamp(30px,6vw,54px);letter-spacing:clamp(5px,2vw,17px);margin:24px 0 10px;filter:drop-shadow(0 8px 9px #936a3020);animation:luxBob 4s ease-in-out infinite}
.birthday-hero h1{font-family:Georgia,"Times New Roman",serif!important;font-size:clamp(46px,8.8vw,92px)!important;font-weight:700!important;letter-spacing:-.055em;line-height:1.02!important;max-width:980px;text-wrap:balance;background:linear-gradient(110deg,#db725f 5%,#b86f73 45%,#9c8155 90%);-webkit-background-clip:text;background-clip:text;color:transparent!important;text-shadow:none!important;margin:14px auto 18px;animation:luxRise .9s .08s both}
.birthday-hero p{font-size:clamp(16px,2.2vw,21px)!important;max-width:650px;line-height:1.8!important;color:#756357!important;animation:luxRise .9s .16s both}
.birthday-age{padding:12px 22px!important;background:#fffdf7d9!important;border:1px solid #fff!important;color:#a46646!important;box-shadow:0 12px 35px #9d74401c!important;animation:luxRise .9s .24s both}
.birthday-hero__button{position:relative;overflow:hidden;padding:17px 30px!important;border-radius:999px!important;background:linear-gradient(115deg,#f47e70,#e98a70 55%,#d89a62)!important;box-shadow:0 13px 0 #d56c5d,0 20px 36px #d66f5a35!important;transition:transform .25s,box-shadow .25s!important;animation:luxRise .9s .32s both}
.birthday-hero__button:after{content:"";position:absolute;inset:-80% auto -80% -45%;width:32%;transform:rotate(22deg);background:linear-gradient(90deg,transparent,#ffffff80,transparent);animation:luxShine 4.8s ease-in-out infinite}
.birthday-hero__button:hover{transform:translateY(-4px)!important;box-shadow:0 16px 0 #d56c5d,0 25px 42px #d66f5a40!important}
.birthday-hero__button:active{transform:translateY(4px)!important;box-shadow:0 5px 0 #d56c5d!important}
.birthday-hero__bottom{margin-top:35px;animation:luxBob 5s ease-in-out infinite}
.birthday-gallery{padding:clamp(52px,8vw,90px) 18px 42px!important}
.birthday-section-heading{margin-bottom:30px!important}
.birthday-section-heading>span{display:inline-grid;place-items:center;width:58px;height:58px;border-radius:20px;background:#fff1d9;box-shadow:0 9px 25px #a77b4218;font-size:27px;transform:rotate(-7deg)}
.birthday-section-heading h2,.birthday-public h2{font-family:Georgia,"Times New Roman",serif!important;font-weight:700!important;letter-spacing:-.035em!important;color:#715248!important}
.birthday-section-heading h2{font-size:clamp(30px,5vw,46px)!important;margin:15px 0 8px!important}
.birthday-section-heading p{color:#978477!important}
.birthday-gallery__frame{max-width:760px!important;height:clamp(340px,68vw,610px)!important;border-radius:34px!important;background:linear-gradient(145deg,#f8e8d3,#e8f0e8)!important;border:8px solid #ffffffc9;box-shadow:0 30px 85px rgba(99,67,42,.16)!important;overflow:hidden}
.birthday-gallery__frame .swiper-slide{padding:8px;background:linear-gradient(145deg,#f8ead8,#edf4ed)!important}
.birthday-gallery__frame img,.birthday-gallery__frame video{border-radius:23px;transition:transform 1s cubic-bezier(.2,.7,.2,1)}
.birthday-gallery__frame .swiper-slide-active img{transform:scale(1.015)}
.birthday-gallery__frame .swiper-pagination{bottom:18px!important}
.birthday-gallery__frame .swiper-pagination-bullet{width:8px;height:8px;background:#fff;opacity:.7;transition:width .25s}
.birthday-gallery__frame .swiper-pagination-bullet-active{width:25px;border-radius:9px;background:#ef8b72!important;opacity:1}
.birthday-surprise-section{padding:28px 18px 38px!important;background:transparent!important}
.surprise-card{position:relative;overflow:hidden;max-width:820px!important;padding:clamp(30px,6vw,58px)!important;border:1px solid #fff!important;border-radius:34px!important;background:linear-gradient(145deg,#fffefa 0%,#fff5e9 100%)!important;box-shadow:var(--lux-shadow)!important;transition:transform .35s,box-shadow .35s}
.surprise-card:before{content:"✦";position:absolute;right:28px;top:18px;font-size:36px;color:#efc77e;opacity:.7;animation:luxTwinkle 3s ease-in-out infinite}
.surprise-card:hover{transform:translateY(-5px);box-shadow:0 30px 85px rgba(105,70,46,.16)!important}
.surprise-kicker{color:#d18a65!important;letter-spacing:.17em!important}
.surprise-card h2{font-size:clamp(29px,5vw,43px)!important}
.surprise-button{padding:15px 25px!important;min-height:52px;border-radius:999px!important;background:linear-gradient(115deg,#ef9879,#e7ae69)!important;box-shadow:0 10px 25px #dc91613d!important;transition:transform .25s,filter .25s,box-shadow .25s!important}
.surprise-button:hover{transform:translateY(-3px) scale(1.025)!important;filter:saturate(1.08);box-shadow:0 15px 30px #dc91614d!important}
.cake-illustration{animation:luxBob 3.5s ease-in-out infinite;filter:drop-shadow(0 15px 18px #95633a25)!important}
.cake-illustration.candles-out{animation:luxPop .55s ease both!important}
.wish-invite-section{padding:clamp(45px,8vw,90px) 16px!important;background:linear-gradient(180deg,#fff5e9aa,#f1f7efaa)!important}
.wish-invite-card{max-width:720px!important;padding:clamp(24px,5vw,52px)!important;border:1px solid #fff!important;border-radius:36px!important;background:rgba(255,254,250,.91)!important;box-shadow:0 30px 90px rgba(93,70,50,.12)!important;backdrop-filter:blur(18px)}
.wish-invite-kicker{display:inline-flex;padding:9px 15px;border-radius:999px;background:#fff1d9;color:#b77a51!important}
.wish-invite-card h2{font-size:clamp(32px,5.5vw,48px)!important}
.wish-input{min-height:58px!important;border:1.5px solid #eee0d0!important;border-radius:17px!important;background:#fffdf8!important;transition:border-color .25s,box-shadow .25s,transform .25s!important}
.wish-input:focus{border-color:#e7a47d!important;box-shadow:0 0 0 5px #efb68c28!important;transform:translateY(-1px)}
.wish-choice,.wish-suggestion{border:1.5px solid #efe2d4!important;background:#fffaf2!important;color:#796354!important;transition:transform .22s,border-color .22s,background .22s,box-shadow .22s!important}
.wish-choice:hover,.wish-suggestion:hover{transform:translateY(-3px);border-color:#e8b18a!important;box-shadow:0 9px 22px #b98b5a18}
.wish-choice.selected,.wish-suggestion.selected{background:#fff0d5!important;border-color:#e9b15e!important;box-shadow:0 0 0 3px #f7dca64a!important;transform:translateY(-2px)}
.wish-suggestions{gap:12px!important}
.wish-suggestion{min-height:68px!important;padding:14px 16px!important;border-radius:18px!important}
.wish-send-button{position:relative;overflow:hidden;min-height:62px!important;border-radius:18px!important;background:linear-gradient(110deg,#ed806f,#e99b6b 55%,#d9a15f)!important;box-shadow:0 8px 0 #d87962,0 17px 32px #d8796230!important;transition:transform .2s,box-shadow .2s!important}
.wish-send-button:hover{transform:translateY(-3px);box-shadow:0 11px 0 #d87962,0 22px 38px #d8796240!important}
.wish-send-button:active{transform:translateY(5px);box-shadow:0 3px 0 #d87962!important}
.wish-send-button:disabled{transform:none;filter:saturate(.6)}
.birthday-public #wishBoard{max-width:1120px;margin:0 auto!important;gap:18px!important}
.birthday-public #wishBoard article{position:relative;padding:25px!important;border:1px solid #fff!important;border-radius:25px!important;background:linear-gradient(145deg,#fffefa,#fff7ec)!important;box-shadow:0 12px 35px #80604412!important;transition:transform .3s,box-shadow .3s!important}
.birthday-public #wishBoard article:hover{transform:translateY(-6px) rotate(-.4deg)!important;box-shadow:0 22px 48px #80604420!important}
.birthday-public #wishBoard article:before{content:"“";position:absolute;right:18px;top:4px;font:70px Georgia,serif;color:#f0d7b7}
.birthday-public #wishBoard article p{position:relative}
.birthday-public>section:has(#wishBoard){background:transparent!important}
.birthday-public footer{background:#fff4e4!important;border-color:#f0e2cf!important;color:#8c7767!important}
.birthday-public .btn{min-height:48px!important;padding:13px 19px!important;border-radius:999px!important;transition:transform .22s,filter .22s!important}
.birthday-public .btn:hover{transform:translateY(-3px);filter:brightness(1.04)}
.birthday-sound-control{border:1px solid #fff!important;background:rgba(255,252,243,.88)!important;box-shadow:0 12px 35px #70503320!important;backdrop-filter:blur(18px)!important}
#musicControlBtn{background:linear-gradient(110deg,#f5c56d,#ee9878)!important;color:#62452e!important;transition:transform .2s!important}
#musicControlBtn:active{transform:scale(.96)}
.birthday-public button:focus-visible,.birthday-public a:focus-visible,.birthday-public input:focus-visible,.birthday-public textarea:focus-visible{outline:3px solid #e7a56d!important;outline-offset:4px}
.lux-cursor,.lux-cursor-dot{position:fixed;left:0;top:0;z-index:10001;pointer-events:none;border-radius:50%;opacity:0;transform:translate(-50%,-50%);will-change:transform}
.lux-cursor{width:34px;height:34px;border:1.5px solid #d88770;background:#f5b77a20;transition:width .2s,height .2s,background .2s,opacity .2s}
.lux-cursor-dot{width:6px;height:6px;background:#d97866}
.lux-cursor.is-visible,.lux-cursor-dot.is-visible{opacity:1}
.lux-cursor.is-hover{width:52px;height:52px;background:#f5b77a38}
.lux-touch-ripple{position:fixed;z-index:10000;pointer-events:none;width:18px;height:18px;border:2px solid #e78e70;border-radius:50%;transform:translate(-50%,-50%);animation:luxRipple .65s ease-out forwards}
.lux-scroll-progress{position:fixed;top:0;left:0;height:3px;width:0;z-index:10002;background:linear-gradient(90deg,#e98b70,#f3c36d,#a7c7ad);box-shadow:0 1px 8px #e98b7070}
.lux-sparkle{position:fixed;z-index:0;pointer-events:none;color:#e5b56c;opacity:.65;animation:luxDrift 8s ease-in-out infinite}
@keyframes luxRise{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:translateY(0)}}
@keyframes luxFloat{50%{transform:translate3d(20px,-18px,0) scale(1.06)}}
@keyframes luxBob{50%{transform:translateY(-9px) rotate(1deg)}}
@keyframes luxShine{0%,55%{left:-45%}85%,100%{left:130%}}
@keyframes luxTwinkle{0%,100%{transform:rotate(0) scale(.9);opacity:.45}50%{transform:rotate(18deg) scale(1.2);opacity:.9}}
@keyframes luxPop{0%{transform:scale(.8) rotate(-8deg)}65%{transform:scale(1.16) rotate(4deg)}100%{transform:scale(1.06)}}
@keyframes luxRipple{to{width:76px;height:76px;opacity:0}}
@keyframes luxDrift{0%,100%{transform:translateY(0) rotate(0)}50%{transform:translateY(-22px) rotate(18deg)}}
@media(max-width:640px){.birthday-hero{min-height:540px;padding:58px 18px 55px!important;border-radius:0 0 34px 34px!important}.birthday-hero__toys{margin:20px 0}.birthday-gallery__frame{height:clamp(300px,62svh,520px)!important;border-width:5px!important;border-radius:26px!important}.surprise-card,.wish-invite-card{border-radius:26px!important}.wish-suggestions{grid-template-columns:1fr!important}.wish-suggestion{text-align:left}.birthday-public #wishBoard article{padding:20px!important}.birthday-sound-control{max-width:calc(100vw - 18px)}}
@media(pointer:coarse){.lux-cursor,.lux-cursor-dot{display:none}.birthday-public button,.birthday-public a{touch-action:manipulation}.birthday-public button:active,.birthday-public a:active{filter:brightness(.97)}}
@media(prefers-reduced-motion:reduce){.birthday-public *,.birthday-public *:before,.birthday-public *:after{animation-duration:.01ms!important;animation-iteration-count:1!important;scroll-behavior:auto!important;transition-duration:.01ms!important}.lux-cursor,.lux-cursor-dot,.lux-touch-ripple,.lux-sparkle{display:none!important}}
</style>
<style id="vaibhav-celebration-experience">
body[data-decor-enabled="false"] .lux-sparkle{display:none!important}
.celebration-trail{position:relative;z-index:2;display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:9px;padding:20px 16px 8px;max-width:1040px;margin:0 auto}
.trail-label{width:100%;text-align:center;color:#a17c67;font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;margin-bottom:3px}
.celebration-trail a{display:inline-flex;align-items:center;gap:8px;padding:10px 15px;border:1px solid #f0dfc9;border-radius:999px;background:#fffdf8;color:#7c6252;text-decoration:none;font-size:12px;font-weight:800;box-shadow:0 7px 20px #9e744010;transition:transform .22s,background .22s,border-color .22s}
.celebration-trail a span{display:grid;place-items:center;width:22px;height:22px;border-radius:50%;background:#fff0d6;color:#b87d4e;font-size:10px}
.celebration-trail a:hover,.celebration-trail a.is-complete{transform:translateY(-3px);background:#fff1dc;border-color:#e9bd83}
.celebration-trail a.is-complete span{background:#e8f3e9;color:#648b68}
.birthday-control-dock{position:fixed;right:20px;bottom:18px;z-index:10010;display:flex;flex-direction:column;align-items:flex-end;gap:10px;max-width:calc(100vw - 24px);font-family:system-ui,-apple-system,sans-serif}
.birthday-dock-launcher{position:relative;display:flex;align-items:center;gap:9px;min-height:54px;padding:0 19px;border:1px solid #fff;border-radius:999px;background:linear-gradient(120deg,#f4c76e,#ee9878);color:#62452e;font-size:13px;font-weight:900;box-shadow:0 12px 32px #9a65413d,0 4px 0 #d98a65;cursor:pointer;transition:transform .2s,box-shadow .2s}
.birthday-dock-launcher:hover{transform:translateY(-3px);box-shadow:0 17px 38px #9a65414a,0 5px 0 #d98a65}
.dock-launcher-icon{font-size:22px;animation:luxBob 2.8s ease-in-out infinite}
.dock-launcher-pulse{position:absolute;inset:-5px;border:1px solid #eaa57c;border-radius:999px;opacity:0;animation:dockPulse 3s ease-out infinite}
.birthday-control-panel{width:min(310px,calc(100vw - 28px));padding:15px;border:1px solid #fff;border-radius:24px;background:rgba(255,252,245,.96);box-shadow:0 22px 65px #61462b35;backdrop-filter:blur(20px);transform-origin:bottom right;animation:dockOpen .22s ease-out}
.birthday-control-panel[hidden]{display:none}
.dock-panel-heading{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;color:#735746;font-weight:900;font-size:13px}
.dock-panel-heading button{width:30px;height:30px;border:0;border-radius:50%;background:#f7e8dc;color:#88624e;font-size:20px;cursor:pointer}
.birthday-sound-control{position:static!important;display:flex!important;align-items:center;justify-content:space-between;gap:9px;width:100%;padding:10px!important;margin:0 0 9px;border:1px solid #f0e0d0!important;border-radius:16px!important;background:#fffaf2!important;box-shadow:none!important;backdrop-filter:none!important}
.birthday-sound-control label{font-size:11px!important}
#musicControlBtn{min-height:39px!important;padding:0 12px!important;font-size:12px!important}
#musicVolume{width:82px!important}
.dock-action{display:flex;align-items:center;justify-content:center;width:100%;min-height:44px;margin-top:8px;padding:10px 13px;border:1px solid #f0dfc9;border-radius:14px;background:#fff4e4;color:#76543d;text-decoration:none;font-size:12px;font-weight:850;cursor:pointer;transition:transform .2s,background .2s}
.dock-action:hover{transform:translateY(-2px);background:#ffebd1}
.dock-auto-hide{margin:10px 2px 0;color:#a18c7d;text-align:center;font-size:10px}
.birthday-balloon{position:absolute;z-index:2;display:grid;place-items:center;width:54px;height:74px;border:0;background:transparent;font-size:43px;cursor:pointer;filter:drop-shadow(0 9px 9px #946a3b25);animation:balloonFloat 5s ease-in-out infinite;transition:transform .2s,opacity .2s}
.birthday-balloon:hover{transform:scale(1.16) rotate(7deg)}
.birthday-balloon.popped{opacity:0;transform:scale(1.8) rotate(22deg);pointer-events:none}
.birthday-balloon.balloon-one{left:4%;top:24%}.birthday-balloon.balloon-two{right:5%;top:18%;animation-delay:-1.5s}.birthday-balloon.balloon-three{left:9%;bottom:9%;animation-delay:-2.5s}.birthday-balloon.balloon-four{right:10%;bottom:7%;animation-delay:-3.4s}
@keyframes dockOpen{from{opacity:0;transform:translateY(12px) scale(.96)}to{opacity:1;transform:none}}
@keyframes dockPulse{0%{transform:scale(.95);opacity:.55}100%{transform:scale(1.2);opacity:0}}
@keyframes balloonFloat{0%,100%{transform:translateY(0) rotate(-5deg)}50%{transform:translateY(-18px) rotate(5deg)}}
@media(max-width:640px){.celebration-trail{gap:7px;padding:16px 10px 4px}.celebration-trail a{padding:9px 11px;font-size:11px}.birthday-control-dock{right:12px;bottom:12px}.birthday-dock-launcher{min-height:50px;padding:0 15px}.birthday-control-panel{width:min(290px,calc(100vw - 24px))}.birthday-balloon{width:39px;height:55px;font-size:32px}.birthday-balloon.balloon-one{left:1%;top:11%}.birthday-balloon.balloon-two{right:1%;top:10%}.birthday-balloon.balloon-three{left:2%;bottom:5%}.birthday-balloon.balloon-four{right:2%;bottom:4%}}
@media(prefers-reduced-motion:reduce){.birthday-balloon,.dock-launcher-icon,.dock-launcher-pulse{animation:none!important}.birthday-control-panel{animation:none!important}.celebration-trail a,.dock-action,.birthday-dock-launcher{transition:none!important}}
</style>

<style id="wish-personal-toggle-interaction">
/* Keep optional personalization immediately before the wish text and make it feel tappable. */
.wish-extra-options{margin:12px 0 14px!important;padding:0!important;border:0!important;background:transparent!important}
.wish-extra-options>summary{display:inline-flex;align-items:center;gap:9px;min-height:42px;padding:10px 15px!important;border:1.5px solid #efc98f;border-radius:999px;background:linear-gradient(135deg,#fff4d9,#fffaf0);color:#8b5d2f!important;font-size:12px!important;font-weight:850!important;box-shadow:0 3px 0 #f2dfc2,0 5px 14px #9b6c2b12;list-style:none;cursor:pointer;user-select:none;-webkit-tap-highlight-color:transparent;transition:transform .18s ease,background .22s ease,border-color .22s ease,box-shadow .22s ease}
.wish-extra-options>summary::-webkit-details-marker{display:none}
.wish-extra-options>summary::after{content:"＋";display:grid;place-items:center;width:21px;height:21px;border-radius:50%;background:#ffe6b5;color:#9a642e;font-size:15px;line-height:1;transition:transform .22s ease,background .22s ease}
.wish-extra-options>summary:hover{transform:translateY(-2px);box-shadow:0 5px 0 #f2dfc2,0 9px 18px #9b6c2b18}
.wish-extra-options>summary:active{transform:scale(.96);box-shadow:0 1px 0 #f2dfc2,0 2px 8px #9b6c2b18}
.wish-extra-options>summary:focus-visible{outline:3px solid #ffb84d;outline-offset:3px}
.wish-extra-options[open]>summary{border-color:#e6a844;background:linear-gradient(135deg,#ffe9bd,#fff3d9);box-shadow:0 0 0 4px #ffb84d22,0 3px 0 #f2dfc2}
.wish-extra-options[open]>summary::after{content:"−";transform:rotate(180deg);background:#ffd88f}
.wish-extra-options[open]>.wish-field-label:first-of-type{animation:wishOptionsReveal .28s ease both}
.wish-extra-options[open] .wish-relationship-choices,.wish-extra-options[open] .wish-suggestions{animation:wishOptionsReveal .32s ease both}
@keyframes wishOptionsReveal{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
@media(prefers-reduced-motion:reduce){.wish-extra-options>summary,.wish-extra-options>summary::after{transition:none}.wish-extra-options[open]>.wish-field-label:first-of-type,.wish-extra-options[open] .wish-relationship-choices,.wish-extra-options[open] .wish-suggestions{animation:none}}
</style>

<style id="wish-choice-upload-visual-feedback">
/* Make relationship choices visibly selected on touch screens and accessible to assistive technology. */
.wish-relationship-choices{gap:9px!important}
.wish-relationship-choices .wish-choice{position:relative;display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:44px!important;padding:10px 14px!important;border:1.5px solid #ead9c5!important;background:#fffaf2!important;color:#765c48!important;font-weight:800!important;box-shadow:0 2px 0 #f1e5d6!important;transition:background .16s ease,border-color .16s ease,color .16s ease,box-shadow .16s ease,transform .16s ease!important;-webkit-tap-highlight-color:transparent}
.wish-relationship-choices .wish-choice:active{transform:scale(.94)!important}
.wish-relationship-choices .wish-choice.selected,.wish-relationship-choices .wish-choice[aria-pressed="true"]{background:linear-gradient(135deg,#ffcf70,#ffad67)!important;border:2px solid #dc813a!important;color:#573313!important;box-shadow:0 0 0 3px #ffcf704d,0 3px 0 #d88a45!important;transform:translateY(-1px)}
.wish-relationship-choices .wish-choice.selected::after,.wish-relationship-choices .wish-choice[aria-pressed="true"]::after{content:"✓";display:inline-grid;place-items:center;width:18px;height:18px;border-radius:50%;background:#fff8e8;color:#9a5a1c;font-size:12px;font-weight:950}
.wish-relationship-choices .wish-choice:focus-visible{outline:3px solid #ffb84d;outline-offset:3px}
.wish-attachment-box{padding:15px!important;border:1px solid #eedcc6!important;border-radius:20px!important;background:linear-gradient(145deg,#fffaf2,#fff4e5)!important;box-shadow:0 7px 20px #805b3010}
.wish-attachment-heading{display:flex;align-items:center;gap:11px;margin-bottom:13px}
.wish-attachment-icon{display:grid;place-items:center;width:40px;height:40px;border-radius:13px;background:#ffebc9;font-size:19px}
.wish-attachment-title{display:block;color:#70513b;font-size:14px;font-weight:900}
.wish-attachment-heading small{display:block;margin-top:3px;color:#9b8877;font-size:11px}
.wish-upload-trigger{display:flex;align-items:center;gap:12px;width:100%;min-height:68px;padding:11px 14px;border:1.5px dashed #dfa96e;border-radius:15px;background:#fffdf9;color:#805638;cursor:pointer;transition:border-color .18s ease,background .18s ease,transform .18s ease;-webkit-tap-highlight-color:transparent}
.wish-upload-trigger:active{transform:scale(.985);background:#fff3df}
.wish-upload-trigger:focus-within,.wish-upload-trigger:focus-visible{outline:3px solid #ffbf68;outline-offset:3px}
.wish-upload-symbol{display:grid;place-items:center;flex:0 0 38px;width:38px;height:38px;border-radius:12px;background:#ffedce;color:#9c632c;font-size:25px;font-weight:500}
.wish-upload-trigger strong{display:block;font-size:13px;font-weight:900}
.wish-upload-trigger small{display:block;margin-top:3px;color:#a08b79;font-size:11px}
.wish-attachment-input{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}
.wish-attachment-input:focus-visible+.wish-attachment-hint{outline:2px solid #ffb84d}
.wish-attachment-hint{display:block;margin:9px 2px 0!important;color:#968372!important;font-size:10px!important;line-height:1.5}
.wish-attachment-list{display:flex;flex-wrap:wrap;gap:7px;margin-top:10px}
.wish-attachment-file{display:inline-flex;align-items:center;gap:6px;max-width:100%;padding:7px 10px!important;border:1px solid #ead7bd;background:#fff!important;color:#76583e!important;border-radius:12px!important;font-size:11px!important;font-weight:750;box-shadow:0 2px 5px #805b300c}
.wish-attachment-file span{overflow-wrap:anywhere}
.wish-attachment-list.has-error{padding:9px 11px;border-radius:10px;background:#fff0ed;color:#b4473c;font-size:12px}
@media(prefers-reduced-motion:reduce){.wish-relationship-choices .wish-choice,.wish-upload-trigger{transition:none!important}}
</style>


<style id="vaibhav-all-memories-pill-square-photo">
/* Keep the memories CTA pill-shaped while presenting its photo in a true square crop. */
.birthday-all-memories-card{
  min-height:0!important;
  height:auto;
  display:flex;
  align-items:center;
  gap:14px;
  padding:10px 14px 10px 10px;
  border-radius:999px;
  background:linear-gradient(110deg,#fff0d6,#ffe4c2 58%,#fcebd5);
  color:#80543f!important;
}
.birthday-all-memories-card>img,
.birthday-all-memories-card__placeholder{
  position:relative;
  inset:auto;
  z-index:0;
  flex:0 0 104px;
  width:104px;
  height:104px;
  aspect-ratio:1/1;
  border-radius:50%;
  object-fit:cover;
  object-position:center;
  overflow:hidden;
  border:3px solid #fffaf0;
  box-shadow:0 3px 12px #76533325;
}
.birthday-all-memories-card__placeholder{
  display:grid;
  place-items:center;
  background:linear-gradient(145deg,#f8dfb8,#ffeecf);
  font-size:42px;
}
.birthday-all-memories-card__shade{
  display:none;
}
.birthday-all-memories-card__content{
  position:relative;
  z-index:1;
  flex:1 1 auto;
  min-width:0;
  display:grid;
  gap:4px;
  padding:8px 42px 8px 0;
  text-shadow:none;
}
.birthday-all-memories-card__content strong{
  color:#80543f;
  font-size:clamp(15px,3.5vw,19px);
  line-height:1.2;
}
.birthday-all-memories-card__content small{
  color:#96745b;
  font-size:12px;
  line-height:1.35;
}
.birthday-all-memories-card__arrow{
  top:50%;
  right:12px;
  transform:translateY(-50%);
  background:#fffaf0;
  color:#85542d;
}
.birthday-all-memories-card:hover .birthday-all-memories-card__arrow,
.birthday-all-memories-card:focus-visible .birthday-all-memories-card__arrow{
  background:#fff;
}
@media(max-width:380px){
  .birthday-all-memories-card{gap:10px;padding:8px 10px 8px 8px}
  .birthday-all-memories-card>img,
  .birthday-all-memories-card__placeholder{
    flex-basis:84px;width:84px;height:84px
  }
  .birthday-all-memories-card__content{padding-right:34px}
  .birthday-all-memories-card__content small{font-size:11px}
  .birthday-all-memories-card__arrow{right:8px;width:30px;height:30px}
}
@media(prefers-reduced-motion:reduce){
  .birthday-all-memories-card{transition:none}
}
</style>

</head>
<body class="birthday-public text-gray-800" data-birthday-theme="<?= $escapeHtml($themePreset) ?>" data-decor-enabled="<?= $floatingDecorEnabled ? 'true' : 'false' ?>" style="--birthday-accent:<?= $escapeHtml($themeAccent) ?>">
 <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
  <!-- 🎉 Interactive Heading -->
  
  <header class="birthday-hero" data-aos="zoom-in">
    <div class="birthday-hero__top">🎈 &nbsp; Aaj ka din hai hamare Kittu ke naam! &nbsp; 🎈</div>
    <div class="birthday-hero__toys" aria-hidden="true">🧸 🦁 🚗 🦕</div>
    <h1><?= $escapeHtml($heroTitle); ?></h1><p><?= $escapeHtml($heroSubtitle); ?></p>
    <div class="birthday-age">🎂 <?= $escapeHtml($birthdayLabel); ?> 🎉</div>
    <a class="birthday-hero__button" href="#wishSection">💌 Vaibhav ko wish bhejein</a>
    <div class="birthday-hero__bottom" aria-hidden="true">⭐ 🎁 🥳 🎁 ⭐</div>
  </header>
  <?php if ($celebrationTrailEnabled): ?>
  <nav class="celebration-trail" aria-label="Celebration journey">
    <span class="trail-label">A little birthday journey</span>
    <a href="#birthdayMemories" data-trail-step="memories"><span>01</span> Memories</a>
    <?php if ($showLetter): ?><a href="#secretLetterSection" data-trail-step="letter"><span>02</span> Love note</a><?php endif; ?>
    <?php if ($showCake): ?><a href="#interactiveCakeSection" data-trail-step="cake"><span>03</span> Cake time</a><?php endif; ?>
    <a href="#wishSection" data-trail-step="wish"><span>04</span> Send a wish</a>
  </nav>
  <?php endif; ?>

  <!-- Vaibhav's little memory gallery -->
<section class="birthday-gallery" id="birthdayMemories" data-aos="fade-up" aria-label="Vaibhav ki yaadein">
  <div class="birthday-section-heading"><span>📸</span><h2>Vaibhav ki pyari yaadein</h2><p>Chhote-chhote pal, dher saari khushiyan!</p></div>
  <div class="birthday-all-memories-wrap"><a class="birthday-all-memories-link" href="https://www.docsansar.com/events?event=vaibhav" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-images" aria-hidden="true"></i> Saari Photos dekhein <span aria-hidden="true"><i class="fa-solid fa-arrow-up-right-from-square"></i></span></a></div>
  <?php
  $allMemoriesUrl = 'https://www.docsansar.com/events?event=vaibhav';
  $allMemoriesImage = '';
  foreach ($birthdayMedia as $memoryPreview) {
      if (!is_array($memoryPreview) || ($memoryPreview['type'] ?? '') !== 'image') continue;
      $candidate = basename((string)($memoryPreview['file'] ?? ''));
      if (preg_match('/^[a-f0-9]{32}\\.(jpg|jpeg|png|webp|gif)$/i', $candidate) && is_file(__DIR__ . '/birthday-media/' . $candidate)) {
          $allMemoriesImage = 'birthday-media/' . rawurlencode($candidate);
          break;
      }
  }
  if ($allMemoriesImage === '' && !empty($featuredImages[0]) && is_file(__DIR__ . '/' . $featuredImages[0])) {
      $allMemoriesImage = $featuredImages[0];
  }
  ?>
  <a class="birthday-all-memories-card" href="<?= $escapeHtml($allMemoriesUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="Doc Sansar par Vaibhav ki saari yaadein dekhein">
    <?php if ($allMemoriesImage !== ''): ?><img src="<?= $escapeHtml($allMemoriesImage) ?>" alt="Vaibhav ki ek pyari yaad" loading="lazy"><?php else: ?><span class="birthday-all-memories-card__placeholder" aria-hidden="true">📸</span><?php endif; ?>
    <span class="birthday-all-memories-card__shade"></span>
    <span class="birthday-all-memories-card__content"><strong>Saari Photos dekhein</strong><small>Vaibhav ke aur bhi pyare pal</small></span>
    <span class="birthday-all-memories-card__arrow" aria-hidden="true">↗</span>
  </a>
  <?php if ($featuredImages || $birthdayMedia): ?>
  <div class="swiper featured-swiper birthday-gallery__frame"><div class="swiper-wrapper">
    <?php foreach ($featuredImages as $img): ?><div class="swiper-slide"><a class="glightbox birthday-lightbox memory-lightbox-link" href="<?= $escapeHtml($img) ?>?v0.5" data-gallery="vaibhav-memories" data-type="image" aria-label="Photo ko full screen mein dekhein"><img src="<?= $escapeHtml($img) ?>?v0.5" class="w-full h-full object-cover" alt="Vaibhav ki yaad" loading="lazy"></a></div><?php endforeach; ?>
    <?php $memoryAudioIndex = 0; foreach ($birthdayMedia as $memory): if (!is_array($memory) || empty($memory['file'])) continue; $mediaFile = basename((string)$memory['file']); if (!preg_match('/^[a-f0-9]{32}\\.(jpg|jpeg|png|webp|gif|mp4|webm|mov|mp3|m4a|wav|ogg|weba|aac|flac)$/i', $mediaFile)) continue; $mediaUrl = 'birthday-media/' . rawurlencode($mediaFile); $memoryType = (string)($memory['type'] ?? 'image'); $memoryCaption = trim((string)($memory['caption'] ?? '')); ?>
      <div class="swiper-slide">
        <?php if ($memoryType === 'video'): ?>
          <a class="glightbox birthday-lightbox memory-lightbox-link memory-media-trigger" href="<?= $escapeHtml($mediaUrl) ?>" data-gallery="vaibhav-memories" data-type="video" aria-label="Video memory ko full screen mein dekhein"><video src="<?= $escapeHtml($mediaUrl) ?>" class="w-full h-full object-cover" muted playsinline preload="metadata" tabindex="-1"></video><span class="memory-media-play" aria-hidden="true">▶</span></a>
        <?php elseif ($memoryType === 'audio'): $memoryAudioIndex++; $audioId = 'birthday-memory-audio-' . $memoryAudioIndex; ?>
          <a class="glightbox birthday-lightbox memory-lightbox-link memory-media-trigger" href="#<?= $escapeHtml($audioId) ?>" data-type="inline" data-gallery="vaibhav-memories" aria-label="Audio memory sunne ke liye kholein"><span class="memory-media-icon" aria-hidden="true">🎵</span><strong>Audio memory</strong><span class="memory-media-open-hint">Tap to listen</span></a>
          <div id="<?= $escapeHtml($audioId) ?>" class="lightbox-inline-media"><div class="lightbox-inline-card"><span class="memory-media-icon">🎵</span><strong><?= $escapeHtml($memoryCaption ?: 'Birthday audio memory') ?></strong><audio src="<?= $escapeHtml($mediaUrl) ?>" controls preload="metadata"></audio></div></div>
        <?php else: ?>
          <a class="glightbox birthday-lightbox memory-lightbox-link" href="<?= $escapeHtml($mediaUrl) ?>" data-gallery="vaibhav-memories" data-type="image" aria-label="Photo ko full screen mein dekhein"><img src="<?= $escapeHtml($mediaUrl) ?>" class="w-full h-full object-cover" alt="<?= $escapeHtml($memoryCaption ?: 'Vaibhav ki yaad') ?>" loading="lazy"></a>
        <?php endif; ?>
        <?php if ($memoryCaption !== ''): ?><div class="memory-caption-overlay"><?= $escapeHtml($memoryCaption); ?></div><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div><div class="swiper-pagination"></div></div>
  <?php else: ?><div class="birthday-gallery__empty"><span>🧸</span><strong>Vaibhav ki photos jald hi yahan aayengi!</strong><small>Birthday Studio se photos, videos aur audio memories add kar sakte hain.</small></div><?php endif; ?>
</section>

<?php if ($showLetter): ?>
<section class="birthday-surprise-section" id="secretLetterSection" data-aos="fade-up">
  <div class="surprise-card letter-card">
    <div class="surprise-kicker">Dil se ek chhota sa note</div>
    <h2><?= $escapeHtml($letterHeading); ?></h2>
    <button type="button" class="surprise-button" id="revealLetter" aria-expanded="false"><?= $escapeHtml($letterButton); ?></button>
    <div class="surprise-reveal" id="letterContent" hidden><p><?= nl2br($escapeHtml($letterMessage)); ?></p></div>
  </div>
</section>
<?php endif; ?>
<?php if ($showCake): ?>
<section class="birthday-surprise-section" id="interactiveCakeSection" data-aos="fade-up">
  <div class="surprise-card cake-card">
    <div class="surprise-kicker">Kittu ke liye ek special moment</div>
    <h2><?= $escapeHtml($cakeHeading); ?></h2>
    <div class="cake-illustration" id="cakeIllustration" aria-hidden="true">🎂</div>
    <p id="cakeMessage" class="cake-message" hidden><?= $escapeHtml($cakeMessage); ?></p>
    <button type="button" class="surprise-button" id="makeWishBtn"><?= $escapeHtml($cakeButton); ?></button>
  </div>
</section>
<?php endif; ?>

<section class="wish-invite-section" id="wishSection" data-aos="fade-up">
  <div class="wish-invite-card">
    <div class="wish-invite-kicker">💛 Chhote Kittu ke liye</div>
    <h2><?= $escapeHtml($wishFormHeading); ?></h2><p class="wish-invite-description"><?= $escapeHtml($wishFormDescription); ?></p>
    <form id="wishForm" autocomplete="on" enctype="multipart/form-data">
      <div class="wish-compact-fields">
        <label class="wish-field-label" for="wishSenderName">Your name</label>
        <input id="wishSenderName" type="text" name="name" value="Guest" placeholder="Enter your name" required maxlength="80" autocomplete="name" class="wish-input" aria-describedby="wishNameHint" />
        <small id="wishNameHint" class="wish-name-hint">A sample name is filled in. Replace it with your own name before sending.</small>
        <details class="wish-extra-options">
          <summary>Wish ko aur personal banayein (optional)</summary>
          <div class="wish-field-label">Aap Vaibhav ke kya lagte hain?</div>
          <div class="wish-relationship-choices" role="group" aria-label="Aapka rishta">
            <button type="button" class="wish-choice" data-relation="Mummy">Mummy</button><button type="button" class="wish-choice" data-relation="Papa">Papa</button>
            <button type="button" class="wish-choice" data-relation="Dada-Dadi">Dada-Dadi</button><button type="button" class="wish-choice" data-relation="Nana-Nani">Nana-Nani</button>
            <button type="button" class="wish-choice" data-relation="Mama-Mami">Mama-Mami</button><button type="button" class="wish-choice" data-relation="Chacha-Chachi">Chacha-Chachi</button>
            <button type="button" class="wish-choice" data-relation="Bua-Fufa">Bua-Fufa</button><button type="button" class="wish-choice" data-relation="Friend">Friend</button>
          </div>
          <input type="hidden" name="pahchan" id="wishRelation" value="">
          <div class="wish-field-label">Ready-made wishes</div>
          <div class="wish-suggestions" role="group" aria-label="Ready-made birthday wishes">
            <button type="button" class="wish-suggestion" data-wish-suggestion="Happy Birthday Vaibhav! Hamesha haste-khelte raho. Dher saara pyaar! 🎂❤️">🎂 Simple wish</button>
            <button type="button" class="wish-suggestion" data-wish-suggestion="Janamdin mubarak ho hamare Kittu! Bhagwan tumhe hamesha khush rakhe. 🌟">🌟 Pyaar bhari wish</button>
            <button type="button" class="wish-suggestion" data-wish-suggestion="Happy 2nd Birthday, little champ! Khoob khelo, masti karo aur hamesha muskurate raho! 🧸🎈">🧸 Little champ</button>
            <button type="button" class="wish-suggestion" data-wish-suggestion="Vaibhav beta, tumhari hasi hamesha aise hi chamakti rahe. Bahut saara aashirwad aur pyaar! 💛">💛 Aashirwad</button>
          </div>
        </details>
        <label class="wish-field-label" for="wishMessage">Vaibhav ke liye wish</label>
        <textarea id="wishMessage" name="message" required maxlength="1200" rows="2" class="wish-input wish-message-input">Happy Birthday Vaibhav! Hamesha haste-khelte raho. Dher saara pyaar! 🎂❤️</textarea>
      </div>
      <div class="wish-attachment-box">
        <div class="wish-attachment-heading">
          <span class="wish-attachment-icon" aria-hidden="true">📎</span>
          <span><span class="wish-attachment-title">Photo, video ya voice wish</span><small>Optional · Apni koi pyari memory share karein</small></span>
        </div>
        <label class="wish-upload-trigger" for="wishAttachments">
          <span class="wish-upload-symbol" aria-hidden="true">＋</span>
          <span><strong>Files choose karein</strong><small>Gallery, video ya audio select karein</small></span>
        </label>
        <input id="wishAttachments" type="file" name="attachments[]" multiple accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime,audio/mpeg,audio/mp4,audio/wav,audio/ogg,audio/webm,application/pdf" class="wish-attachment-input" aria-describedby="wishAttachmentHint">
        <small id="wishAttachmentHint" class="wish-attachment-hint">Maximum 3 files · 10 MB each · Photo, video, audio or PDF</small>
        <div id="wishAttachmentList" class="wish-attachment-list" aria-live="polite"></div>
      </div>
      <p class="wish-privacy-note">Aapki wish aur attached media birthday page par dikh sakte hain. Sirf wahi share karein jo sabke saath share karna theek ho. ❤️</p>
      <button id="wishSubmitButton" type="submit" class="wish-send-button">💌 <?= $escapeHtml($submitLabel); ?></button>
      <p id="wishFeedback" class="wish-feedback" role="status" aria-live="polite"></p>
    </form>  </div>
</section>

  

  <!-- 🎁 Wishes -->
  <section class="px-4 py-16 bg-gradient-to-br from-pink-50 to-pink-100" data-aos="fade-up">
    <div class="max-w-6xl mx-auto">
      <h2 class="text-3xl font-extrabold text-center text-pink-700 mb-10"><?= $escapeHtml($wishBoardHeading); ?></h2>
      <div id="wishBoard" class="grid sm:grid-cols-2 md:grid-cols-3 gap-8"></div>
    </div>
  </section>

<?php if ($showFinal): ?>
<section class="birthday-surprise-section" id="finalCelebrationSection" data-aos="fade-up">
  <div class="surprise-card final-card">
    <div class="surprise-kicker">Sabki taraf se dher saara pyaar</div>
    <h2><?= $escapeHtml($finalHeading); ?></h2>
    <p><?= $escapeHtml($finalMessage); ?></p>
    <button type="button" class="surprise-button" id="celebrateAgain">Chalo aur celebrate karein ✨</button>
  </div>
</section>
<?php endif; ?>

  <!-- 🎵 Background Music -->
  <?php if ($musicEnabled): ?>
  <audio id="bgMusic" loop preload="auto" volume="0.12"><source src="<?= $escapeHtml($musicSource); ?>" type="audio/mpeg"></audio>
  <?php endif; ?>
<!-- Share Buttons Section -->
<div class="mt-6 flex flex-wrap gap-3 justify-center items-center">
  <a href="https://wa.me/?text=<?= rawurlencode($shareMessage) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-success">📱 WhatsApp</a>
  <a href="https://twitter.com/intent/tweet?text=<?= rawurlencode($shareMessage) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-info text-white">Share on X</a>
  <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($shareUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">📘 Facebook</a>
  <a href="https://t.me/share/url?url=<?= rawurlencode($shareUrl) ?>&text=<?= rawurlencode($shareMessage) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">💬 Telegram</a>
  <button onclick="copyBirthdayLink()" class="btn btn-dark" type="button">🔗 Copy Link</button>
  
</div>

<!-- Persistent side share action -->
<button type="button" class="birthday-side-share" id="birthdaySideShare" onclick="shareGeneral()" aria-label="Share Vaibhav's birthday page">
  <span aria-hidden="true"><i class="fa-solid fa-share-nodes"></i></span><span>Share</span>
</button>

<!-- Scripts -->
<script>
  const shareText = <?= json_encode($shareMessage, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

  function copyBirthdayLink() {
    navigator.clipboard.writeText(<?= json_encode($shareUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>).then(() => {
      alert("🎉 Link copied to clipboard! Share it with your friends!");
    }).catch(() => {
      alert("❌ Failed to copy. Please copy manually.");
    });
  }

  function shareGeneral() {
    if (navigator.share) {
      navigator.share({
        title: "<?= $escapeHtml($name); ?>’s Birthday 🎂",
        text: shareText,
        url: <?= json_encode($shareUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>
      }).then(() => {
        console.log('Shared successfully');
      }).catch((err) => {
        alert("❌ Share failed or cancelled.");
        console.log(err);
      });
    } else {
      alert("ℹ️ Your browser does not support the share feature.");
    }
  }
</script>

<!-- Basic Button Styles (adjust as per Bootstrap or your custom CSS) -->
<style>
  .birthday-side-share{position:fixed;right:12px;top:46%;transform:translateY(-50%);z-index:75;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;min-width:54px;min-height:76px;padding:11px 9px;border:1px solid #f4a84c;border-radius:999px;background:linear-gradient(160deg,#ffd36f,#ff986d);color:#633d20;font:800 12px/1.1 system-ui,-apple-system,'Segoe UI',sans-serif;box-shadow:0 7px 22px #8b552b38;cursor:pointer;transition:transform .18s ease,box-shadow .18s ease}
  .birthday-side-share span:first-child{display:grid;place-items:center;width:30px;height:30px;border-radius:50%;background:#fff8e8;color:#9b572d;font-size:22px;line-height:1}
  .birthday-side-share:hover,.birthday-side-share:focus-visible{transform:translateY(-50%) scale(1.06);box-shadow:0 10px 26px #8b552b50;outline:3px solid #ffb84d66;outline-offset:3px}
  .birthday-side-share:active{transform:translateY(-50%) scale(.96)}
  @media(max-width:520px){.birthday-side-share{right:7px;top:43%;min-width:46px;min-height:66px;padding:8px 7px;font-size:11px}.birthday-side-share span:first-child{width:27px;height:27px;font-size:20px}}
  @media(prefers-reduced-motion:reduce){.birthday-side-share{transition:none}}
  .btn {
    display: inline-block;
    padding: 0.5rem 1.2rem;
    font-weight: 600;
    border-radius: 30px;
    text-align: center;
    text-decoration: none;
    transition: background-color 0.2s;
  }
  .btn:hover {
    opacity: 0.9;
  }
  .btn-success { background-color: #25D366; color: white; }
  .btn-primary { background-color: #1877F2; color: white; }
  .btn-info    { background-color: #1DA1F2; color: white; }
  .btn-secondary { background-color: #0088cc; color: white; }
  .btn-dark { background-color: #333; color: white; }
  .btn-warning { background-color: #ffc107; color: black; }
  .btn-pink {
    background-color: #E1306C;
    color: white;
    padding: 0.5rem 1.2rem;
    border-radius: 30px;
    text-decoration: none;
    font-weight: bold;
  }
  .birthday-sound-control{position:fixed;right:12px;bottom:12px;z-index:60;display:flex;align-items:center;gap:10px;padding:9px 12px;border:1px solid #ead9c4;border-radius:999px;background:#fffdf2f2;box-shadow:0 8px 24px #674b2a24;backdrop-filter:blur(10px);color:#654c36}
  #musicControlBtn{min-height:42px;padding:0 14px;border:0;border-radius:999px;background:#ffb84d;color:#573b1d;font-size:14px;font-weight:850;display:inline-flex;align-items:center;justify-content:center;gap:7px;cursor:pointer}
  .birthday-sound-control label{display:flex;align-items:center;gap:7px;font-size:11px;font-weight:750;white-space:nowrap}
  #musicVolume{width:76px;accent-color:#e99542;cursor:pointer}
  @media(max-width:480px){.birthday-sound-control{right:9px;bottom:9px;gap:7px;padding:7px 9px}#musicControlBtn{min-height:40px;padding:0 11px;font-size:12px}#musicVolume{width:62px}}
</style>

<div class="birthday-control-dock" id="birthdayControlDock">
  <button type="button" class="birthday-dock-launcher" id="celebrationDockToggle" aria-expanded="false" aria-controls="celebrationControlPanel" aria-label="Open celebration controls">
    <span class="dock-launcher-icon">🎉</span><span class="dock-launcher-label">Fun controls</span><span class="dock-launcher-pulse"></span>
  </button>
  <div class="birthday-control-panel" id="celebrationControlPanel" hidden role="group" aria-label="Celebration controls">
    <div class="dock-panel-heading"><span>✨ Celebration corner</span><button type="button" id="celebrationDockClose" aria-label="Close controls">×</button></div>
    <?php if ($musicEnabled): ?>
    <div class="birthday-sound-control" role="group" aria-label="Sound controls">
      <button id="musicControlBtn" type="button" aria-label="Turn birthday music on" aria-pressed="false">🔇 Sound off</button>
      <label for="musicVolume">Volume <input id="musicVolume" type="range" min="0" max="30" value="12" step="1"></label>
    </div>
    <?php endif; ?>
    <?php if ($confettiEnabled): ?><button type="button" class="dock-action" id="dockConfettiButton">🎊 Make it rain confetti</button><?php endif; ?>
    <a class="dock-action" href="#wishSection" id="dockWishLink">💌 Send Vaibhav a wish</a>
    <p class="dock-auto-hide">This little panel closes itself after a moment.</p>
  </div>
</div>


<footer class="bg-pink-50 text-center py-6 mt-10 border-t border-pink-100 text-sm text-gray-600">
  <p class="mb-2">Made with ❤️ to celebrate <strong><?= $escapeHtml($name); ?>'s Birthday</strong></p>
  <p>Thanks for being part of the joy! 🎉</p>
  <p class="mt-2 text-xs text-gray-400">© <?= date('Y') ?> <a href="/">DocSansar.com</a> • All wishes stored safely for a lifetime 🎁</p>
</footer>

  <!-- Confetti JS -->
  <canvas class="confetti" id="confetti-canvas"></canvas>
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  
  <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
      AOS.init();
    const confettiCanvas = document.getElementById("confetti-canvas");
    const confettiInstance = confetti.create(confettiCanvas, { resize: true });

    const stopBirthdayMedia = (except = null) => {
      document.querySelectorAll("video, audio").forEach(media => {
        if (media === except) return;
        media.pause();
        if (media.tagName === "VIDEO") {
          try { media.currentTime = 0; } catch (error) {}
        }
      });
    };
    const memorySwiper = new Swiper(".featured-swiper", {
      loop: true,
      autoplay: { delay: 3000, disableOnInteraction: true, pauseOnMouseEnter: true },
      pagination: { el: ".swiper-pagination", clickable: true },
      on: {
        touchStart(swiper) { if (swiper.autoplay) swiper.autoplay.stop(); },
        sliderFirstMove(swiper) { if (swiper.autoplay) swiper.autoplay.stop(); },
        slideChangeTransitionStart() { stopBirthdayMedia(); }
      }
    });
    const memoryCarousel = document.querySelector(".featured-swiper");
    if (memoryCarousel) {
      ["pointerdown", "focusin"].forEach(eventName => memoryCarousel.addEventListener(eventName, () => {
        if (memorySwiper.autoplay) memorySwiper.autoplay.stop();
      }, { passive: true }));
    }

    // Capture media play events, including media created inside GLightbox.
    document.addEventListener("play", event => {
      const media = event.target;
      if (!(media instanceof HTMLMediaElement)) return;
      stopBirthdayMedia(media);
      if (media.tagName === "VIDEO") {
        media.muted = false;
        media.removeAttribute("muted");
        media.volume = 1;
      }
    }, true);
    document.addEventListener("ended", event => {
      if (event.target instanceof HTMLMediaElement) event.target.pause();
    }, true);

    // One library-powered viewer for every memory and every visitor-wish attachment.
    const memoryLightbox = GLightbox({
      selector: "a.birthday-lightbox",
      touchNavigation: true,
      loop: true,
      zoomable: true,
      draggable: true,
      closeOnOutsideClick: true,
      autoplayVideos: false,
      openEffect: "zoom",
      closeEffect: "fade",
      slideEffect: "slide"
    });
    memoryLightbox.on("open", () => {
      stopBirthdayMedia();
      if (memorySwiper.autoplay) memorySwiper.autoplay.stop();
    });
    memoryLightbox.on("slide_changed", () => stopBirthdayMedia());
    memoryLightbox.on("close", () => stopBirthdayMedia());

    $('img.lazy').each(function () {
      const img = $(this);
      img.on('load', () => img.addClass('loaded')).attr('src', img.attr('src'));
    });

let wishMediaInlineSequence = 0;
function loadWishes() {
  $.getJSON("wishes.json?ts=" + new Date().getTime(), function (data) {
    $("#wishBoard").html("");

    $(".wish-inline-lightbox").remove();
    data.reverse().forEach((wish, wishIndex) => {
      const name = wish.name?.trim() || "Anonymous";
      const message = wish.message?.trim() || "🎉 Sent you best wishes!";
      const pahchan = wish.pahchan?.trim();
      const timestamp = parseInt(wish.timestamp);
      let time = "Unknown time";

      if (!isNaN(timestamp)) {
        const dt = new Date(timestamp * 1000);
        time = dt.toLocaleString("en-IN", {
          day: "2-digit",
          month: "short",
          hour: "2-digit",
          minute: "2-digit",
          hour12: true
        }).replace(",", "");
      }

      const $card = $("<article>").addClass("bg-white p-6 rounded-2xl shadow-xl transition transform duration-300").attr("data-aos", "fade-up");
      const $wishText = $("<p>").addClass("text-gray-800 text-lg font-semibold whitespace-pre-line").text("“" + message + "”");
      const $byline = $("<p>").addClass("mt-2 text-sm text-pink-600 font-bold").text("– " + name);
      if (pahchan) {
        $("<span>").addClass("text-gray-500 font-medium").text(" (" + pahchan + ")").appendTo($byline);
      }
      const $date = $("<p>").addClass("text-xs text-gray-400 mt-1").text(time);$card.append($wishText,$byline,$date);
      if(Array.isArray(wish.media)&&wish.media.length){
        const $attachments=$("<div>").addClass("wish-board-attachments");
        const wishGallery="birthday-wish-"+String(wishIndex)+"-"+String(wish.timestamp||Date.now());
        wish.media.slice(0,3).forEach(item=>{
          if(!item||typeof item.file!=="string"||!/^[a-f0-9]{32}\.(jpg|jpeg|png|webp|gif|mp4|webm|mov|mp3|m4a|wav|ogg|weba|aac|pdf)$/i.test(item.file))return;
          const url="wish-media/"+encodeURIComponent(item.file);
          const $link=$("<a>").addClass("birthday-lightbox wish-media-trigger").attr({"data-gallery":wishGallery,"aria-label":"Open wish attachment"});
          if(item.type==="image"){
            $link.attr({href:url,"data-type":"image"}).append($("<img>").attr({src:url,alt:"Wish photo",loading:"lazy"}));
          }else if(item.type==="video"){
            $link.attr({href:url,"data-type":"video"}).append($("<video>").attr({src:url,muted:true,playsinline:true,preload:"metadata"}),$("<span>").addClass("wish-media-label").text("▶ Play video"));
          }else if(item.type==="audio"){
            const inlineId="wish-audio-"+(++wishMediaInlineSequence);
            $link.attr({href:"#"+inlineId,"data-type":"inline"}).append($("<span>").addClass("wish-media-label").text("🎵 Listen to audio"));
            $("<div>").attr("id",inlineId).addClass("lightbox-inline-media wish-inline-lightbox").append(
              $("<div>").addClass("lightbox-inline-card").append(
                $("<span>").addClass("memory-media-icon").text("🎵"),
                $("<strong>").text("Voice / audio wish"),
                $("<audio>").attr({src:url,controls:true,preload:"metadata"})
              )
            ).appendTo(document.body);
          }else if(item.type==="document"){
            const inlineId="wish-pdf-"+(++wishMediaInlineSequence);
            $link.attr({href:"#"+inlineId,"data-type":"inline"}).append($("<span>").addClass("wish-media-label").text("📄 View PDF"));
            $("<div>").attr("id",inlineId).addClass("lightbox-inline-media").append(
              $("<div>").addClass("lightbox-inline-card").append(
                $("<strong>").text("Attached PDF"),
                $("<iframe>").attr({src:url,title:"Wish PDF attachment",loading:"lazy"})
              )
            ).appendTo(document.body);
          }
          if($link.attr("href"))$attachments.append($link);
        });
        if($attachments.children().length)$card.append($attachments);
      }
      $("#wishBoard").append($card);
    });

    memoryLightbox.reload();
    AOS.refresh();
  }).fail(function () {
    $("#wishBoard").html(`
      <div class="text-center text-red-500 p-4">
        Abhi wishes load nahi ho paayi. Thodi der baad try kijiye 🙂.
      </div>
    `);
  });
}



    const wishForm=$("#wishForm"), wishName=document.getElementById("wishSenderName"), wishMessage=document.getElementById("wishMessage"), wishRelation=document.getElementById("wishRelation"), wishFeedback=document.getElementById("wishFeedback"), wishSubmit=document.getElementById("wishSubmitButton");
    const wishAttachments=document.getElementById("wishAttachments"), wishAttachmentList=document.getElementById("wishAttachmentList"), defaultWish="Happy Birthday Vaibhav! Hamesha haste-khelte raho. Dher saara pyaar! 🎂❤️";
    try{const saved=localStorage.getItem("vaibhavWishSenderName");if(saved&& !["aapka naam","guest","your name"].includes(saved.trim().toLowerCase())){wishName.value=saved;document.getElementById("wishNameHint").textContent="Your previous name is remembered on this device. You can change it anytime.";}}catch(e){}
    wishAttachments.addEventListener("change",()=>{const files=Array.from(wishAttachments.files||[]);wishAttachmentList.innerHTML="";wishAttachmentList.classList.toggle("has-files",files.length>0);if(files.length>3||files.some(file=>file.size>10*1024*1024)){wishAttachmentList.textContent="Maximum 3 files choose karein; har file 10 MB se chhoti honi chahiye.";wishAttachmentList.classList.add("has-error");wishAttachments.value="";return;}wishAttachmentList.classList.remove("has-error");files.forEach(file=>{const tag=document.createElement("span");tag.className="wish-attachment-file";const icon=document.createElement("b");icon.textContent=file.type.startsWith("image/")?"🖼️":file.type.startsWith("video/")?"🎬":file.type.startsWith("audio/")?"🎵":"📄";const name=document.createElement("span");name.textContent=file.name;tag.append(icon,name);wishAttachmentList.appendChild(tag);});});
    document.querySelectorAll("[data-wish-suggestion]").forEach(button=>button.addEventListener("click",()=>{wishMessage.value=button.dataset.wishSuggestion||defaultWish;document.querySelectorAll("[data-wish-suggestion]").forEach(item=>item.classList.toggle("selected",item===button));}));
    document.querySelectorAll("[data-relation]").forEach(button=>{button.setAttribute("aria-pressed","false");button.addEventListener("click",()=>{const relation=button.dataset.relation||"";wishRelation.value=wishRelation.value===relation?"":relation;document.querySelectorAll("[data-relation]").forEach(item=>{const selected=item===button&&wishRelation.value!=="";item.classList.toggle("selected",selected);item.setAttribute("aria-pressed",selected?"true":"false");});});});
    let allowPrefilledWishOnce=false;
    wishForm.on("submit",function(e){
      if(allowPrefilledWishOnce){allowPrefilledWishOnce=false;return;}
      const senderName=wishName.value.trim();
      const messageText=wishMessage.value.trim();
      if(senderName.toLowerCase()==="guest"){
        e.preventDefault();e.stopImmediatePropagation();
        Swal.fire({icon:"info",title:"Apna naam likhna na bhoolein",text:"Naam mein abhi sample “Guest” bhara hua hai. Kripya apna naam likhkar phir wish bhejein.",confirmButtonText:"Naam edit karein",confirmButtonColor:"#f47e70",customClass:{popup:"birthday-swal-popup"},buttonsStyling:true}).then(()=>wishName.focus());
        return;
      }
      if(messageText===defaultWish){
        e.preventDefault();e.stopImmediatePropagation();
        Swal.fire({icon:"question",title:"Sample wish hi bhejni hai?",text:"Message mein abhi pre-filled birthday wish hai. Aap ise edit kar sakte hain, ya isi wish ko bhej sakte hain.",showCancelButton:true,confirmButtonText:"Edit wish",cancelButtonText:"Isi ko bhejein",confirmButtonColor:"#f47e70",cancelButtonColor:"#e5a84b",reverseButtons:true,customClass:{popup:"birthday-swal-popup"}}).then(result=>{
          if(result.isConfirmed){wishMessage.focus();wishMessage.scrollIntoView({behavior:"smooth",block:"center"});return;}
          if(result.dismiss===Swal.DismissReason.cancel){allowPrefilledWishOnce=true;wishForm.trigger("submit");}
        });
      }
    });
    wishForm.on("submit",function(e){e.preventDefault();if(!wishName.value.trim()||wishName.value.trim().toLowerCase()==="aapka naam"){wishFeedback.textContent="Kripya ‘Aapka naam’ ki jagah apna naam likhein.";wishName.focus();return;}if(!wishMessage.value.trim()){wishFeedback.textContent="Ek chhoti si wish chuniye ya likh dijiye.";wishMessage.focus();return;}if(wishAttachments.files.length>3||Array.from(wishAttachments.files).some(file=>file.size>10*1024*1024)){wishFeedback.textContent="Maximum 3 attachments aur 10 MB per file allowed hai.";return;}
      wishSubmit.disabled=true;wishSubmit.textContent="⏳ Wish bheji ja rahi hai...";wishFeedback.textContent="Bas ek pal...";const formData=new FormData(wishForm[0]);
      $.ajax({url:"submit_wish.php",method:"POST",data:formData,dataType:"json",processData:false,contentType:false}).done(function(res){if(res.success){try{localStorage.setItem("vaibhavWishSenderName",wishName.value.trim());}catch(e){}wishMessage.value=defaultWish;wishAttachments.value="";wishAttachmentList.innerHTML="";wishRelation.value="";document.querySelectorAll("[data-relation], [data-wish-suggestion]").forEach(item=>{item.classList.remove("selected");if(item.hasAttribute("data-relation"))item.setAttribute("aria-pressed","false");});wishFeedback.textContent="🎉 Aapki wish Vaibhav tak pahunch gayi! Shukriya ❤️";loadWishes();if(typeof confettiInstance==="function")confettiInstance({particleCount:120,spread:160});}else{wishFeedback.textContent=res.error||"Wish send nahi ho paayi. Dobara try kijiye.";}}).fail(function(xhr){wishFeedback.textContent=xhr.responseJSON&&xhr.responseJSON.error?xhr.responseJSON.error:"Internet ya server mein dikkat hai. Ek baar phir try kijiye.";}).always(function(){wishSubmit.disabled=false;wishSubmit.textContent="💌 <?= $escapeHtml($submitLabel); ?>";});
    });
    loadWishes();
  const revealLetter = document.getElementById('revealLetter');
  if (revealLetter) revealLetter.addEventListener('click', () => {
    const letter = document.getElementById('letterContent');
    letter.hidden = false;
    revealLetter.setAttribute('aria-expanded', 'true');
    revealLetter.hidden = true;
  });
  const makeWishBtn = document.getElementById('makeWishBtn');
  if (makeWishBtn) makeWishBtn.addEventListener('click', () => {
    document.getElementById('cakeIllustration').classList.add('candles-out');
    document.getElementById('cakeMessage').hidden = false;
    makeWishBtn.textContent = 'Kittu ke liye dher saari wishes! ✨';
    makeWishBtn.disabled = true;
    if (<?= $confettiEnabled ? 'true' : 'false' ?> && typeof confettiInstance === 'function') confettiInstance({particleCount: 90, spread: 75, origin: {y: 0.68}});
  });
  const celebrateAgain = document.getElementById('celebrateAgain');
  if (celebrateAgain) celebrateAgain.addEventListener('click', () => {
    if (<?= $confettiEnabled ? 'true' : 'false' ?> && typeof confettiInstance === 'function') confettiInstance({particleCount: 110, spread: 95, origin: {y: 0.65}});
  });
  const music = document.getElementById("bgMusic");
  const musicBtn = document.getElementById("musicControlBtn");
  const musicVolume = document.getElementById("musicVolume");
  if (music && musicBtn && musicVolume) {
    music.volume = 0.12;
    let skipMusicToggleOnce = false;
    let musicStartPending = false;
    let musicStartedByGesture = false;
    const syncSoundControl = () => {
      const isPlaying = !music.paused && !music.muted && music.volume > 0;
      musicBtn.textContent = isPlaying ? "🔊 Sound on" : "🔇 Sound off";
      musicBtn.setAttribute("aria-pressed", isPlaying ? "true" : "false");
      musicBtn.setAttribute("aria-label", isPlaying ? "Turn birthday music off" : "Turn birthday music on");
      musicVolume.value = String(Math.round(music.volume * 100));
    };
    const removeFirstInteractionListeners = () => {
      document.removeEventListener("pointerdown", startMusicOnFirstInteraction, true);
      document.removeEventListener("touchend", startMusicOnFirstInteraction, true);
      document.removeEventListener("click", startMusicOnFirstInteraction, true);
      document.removeEventListener("keydown", startMusicOnFirstInteraction, true);
    };
    const startMusicOnFirstInteraction = (event) => {
      if (musicStartedByGesture || musicStartPending || (!music.paused && !music.muted && music.volume > 0)) {
        if (!music.paused && !music.muted && music.volume > 0) {
          musicStartedByGesture = true;
          removeFirstInteractionListeners();
        }
        return;
      }
      if (event && event.isTrusted === false) return;
      music.muted = false;
      if (music.volume <= 0) music.volume = 0.12;
      musicVolume.value = String(Math.round(music.volume * 100));
      if (event && event.target instanceof Element && event.target.closest("#musicControlBtn")) skipMusicToggleOnce = true;

      // Keep listening until playback actually succeeds. A rejected first attempt
      // (common while mobile media is still loading) must not consume the gesture hook.
      musicStartPending = true;
      const playAttempt = music.play();
      if (playAttempt && typeof playAttempt.then === "function") {
        playAttempt.then(() => {
          musicStartPending = false;
          musicStartedByGesture = true;
          removeFirstInteractionListeners();
          syncSoundControl();
        }).catch(() => {
          musicStartPending = false;
          syncSoundControl();
          musicBtn.textContent = "▶ Tap to play";
          musicBtn.setAttribute("aria-label", "Tap to play birthday music");
        });
      } else {
        musicStartPending = false;
        musicStartedByGesture = !music.paused;
        if (musicStartedByGesture) removeFirstInteractionListeners();
        syncSoundControl();
      }
    };
    document.addEventListener("pointerdown", startMusicOnFirstInteraction, true);
    document.addEventListener("touchend", startMusicOnFirstInteraction, true);
    document.addEventListener("click", startMusicOnFirstInteraction, true);
    document.addEventListener("keydown", startMusicOnFirstInteraction, true);
    musicBtn.addEventListener("click", () => {
      if (skipMusicToggleOnce) { skipMusicToggleOnce = false; syncSoundControl(); return; }
      if (music.paused || music.muted || music.volume === 0) {
        music.muted = false;
        if (music.volume === 0) music.volume = 0.12;
        musicVolume.value = String(Math.round(music.volume * 100));
        music.play().then(syncSoundControl).catch(() => {
          musicBtn.textContent = "▶ Tap to play";
          musicBtn.setAttribute("aria-label", "Tap to play birthday music");
        });
      } else {
        music.pause();
        syncSoundControl();
      }
    });
    musicVolume.addEventListener("input", () => {
      const nextVolume = Math.min(0.3, Math.max(0, Number(musicVolume.value) / 100));
      music.volume = nextVolume;
      if (nextVolume === 0) {
        music.pause();
      } else if (!music.paused) {
        music.muted = false;
      }
      syncSoundControl();
    });
    music.addEventListener("play", syncSoundControl);
    music.addEventListener("pause", syncSoundControl);
    syncSoundControl();
  }
  </script>



<script id="vaibhav-premium-interactions">
(function(){
 const reduce=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
 const progress=document.createElement('div');progress.className='lux-scroll-progress';document.body.appendChild(progress);
 const updateProgress=()=>{const max=document.documentElement.scrollHeight-window.innerHeight;progress.style.width=(max>0?window.scrollY/max*100:0)+'%';};
 window.addEventListener('scroll',updateProgress,{passive:true});updateProgress();
 if(!reduce){
   const symbols=['✦','✧','♡','·'];for(let i=0;i<9;i++){const s=document.createElement('span');s.className='lux-sparkle';s.textContent=symbols[i%symbols.length];s.style.left=(5+(i*11)%91)+'%';s.style.top=(12+(i*17)%78)+'%';s.style.fontSize=(12+(i%3)*8)+'px';s.style.animationDelay=(-i*.8)+'s';document.body.appendChild(s);}
 }
 const fine=window.matchMedia&&window.matchMedia('(pointer: fine)').matches;
 if(fine&&!reduce){
   const ring=document.createElement('span'),dot=document.createElement('span');ring.className='lux-cursor';dot.className='lux-cursor-dot';document.body.append(ring,dot);
   let x=-50,y=-50,dx=-50,dy=-50,raf=0;
   document.addEventListener('pointermove',e=>{x=e.clientX;y=e.clientY;ring.classList.add('is-visible');dot.classList.add('is-visible');dot.style.transform='translate('+x+'px,'+y+'px) translate(-50%,-50%)';if(!raf)raf=requestAnimationFrame(function move(){dx+=(x-dx)*.22;dy+=(y-dy)*.22;ring.style.transform='translate('+dx+'px,'+dy+'px) translate(-50%,-50%)';if(Math.abs(x-dx)+Math.abs(y-dy)>.3)raf=requestAnimationFrame(move);else raf=0;});},{passive:true});
   document.addEventListener('pointerover',e=>{if(e.target.closest('a,button,input,textarea,select,[role=button]'))ring.classList.add('is-hover');});
   document.addEventListener('pointerout',e=>{if(e.target.closest('a,button,input,textarea,select,[role=button]'))ring.classList.remove('is-hover');});
   document.addEventListener('pointerleave',()=>{ring.classList.remove('is-visible');dot.classList.remove('is-visible');});
 }
 document.addEventListener('pointerdown',e=>{if(reduce)return;const ripple=document.createElement('span');ripple.className='lux-touch-ripple';ripple.style.left=e.clientX+'px';ripple.style.top=e.clientY+'px';document.body.appendChild(ripple);ripple.addEventListener('animationend',()=>ripple.remove(),{once:true});},{passive:true});
})();
</script>
<script id="vaibhav-celebration-interactions">
(function(){
 const config=<?= json_encode(['confetti'=>$confettiEnabled,'decor'=>$floatingDecorEnabled,'trail'=>$celebrationTrailEnabled,'autoHide'=>$controlsAutoHideSeconds], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
 const reduce=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
 const canCelebrate=!!config.confetti&&!reduce&&typeof window.confetti==='function';
 let confettiBurst=()=>{};
 if(canCelebrate){
   confettiBurst=(originY=.58,heavy=false)=>{
     const base={origin:{x:.5,y:originY},zIndex:10020,disableForReducedMotion:true};
     window.confetti({...base,particleCount:heavy?150:75,spread:heavy?105:78,startVelocity:heavy?48:36,gravity:.9,scalar:heavy?1.05:.85});
     if(heavy)setTimeout(()=>window.confetti({...base,particleCount:70,spread:125,startVelocity:30,gravity:1.05,origin:{x:.5,y:originY+.04}}),180);
   };
   setTimeout(()=>confettiBurst(.38,true),850);
 }
 window.vaibhavCelebrate=confettiBurst;
 const dock=document.getElementById('birthdayControlDock'),panel=document.getElementById('celebrationControlPanel'),toggle=document.getElementById('celebrationDockToggle'),close=document.getElementById('celebrationDockClose');
 let hideTimer=null;
 function closeDock(){if(!panel||panel.hidden)return;panel.hidden=true;toggle.setAttribute('aria-expanded','false');toggle.setAttribute('aria-label','Open celebration controls');clearTimeout(hideTimer);}
 function scheduleHide(){clearTimeout(hideTimer);hideTimer=setTimeout(()=>{if(panel&&!panel.querySelector(':focus-visible'))closeDock();else scheduleHide();},Math.max(3,Math.min(15,Number(config.autoHide)||5))*1000);}
 function openDock(){if(!panel)return;panel.hidden=false;toggle.setAttribute('aria-expanded','true');toggle.setAttribute('aria-label','Close celebration controls');scheduleHide();}
 if(toggle&&panel){
   toggle.addEventListener('click',()=>panel.hidden?openDock():closeDock());
   if(close)close.addEventListener('click',closeDock);
   panel.addEventListener('pointerdown',scheduleHide,{passive:true});
   panel.addEventListener('input',scheduleHide);
   panel.addEventListener('click',event=>{if(event.target.closest('a,button'))scheduleHide();});
   panel.addEventListener('focusin',()=>clearTimeout(hideTimer));
   panel.addEventListener('focusout',()=>setTimeout(()=>{if(!panel.contains(document.activeElement))scheduleHide();},80));
   document.addEventListener('pointerdown',event=>{if(!dock.contains(event.target)&&!panel.hidden)scheduleHide();},{passive:true});
   document.addEventListener('keydown',event=>{if(event.key==='Escape')closeDock();});
 }
 const confettiButton=document.getElementById('dockConfettiButton');
 if(confettiButton)confettiButton.addEventListener('click',()=>{confettiBurst(.55,true);confettiButton.textContent='✨ More birthday magic!';});
 const hero=document.querySelector('.birthday-hero');
 if(hero&&config.decor&&!reduce){
   ['balloon-one','balloon-two','balloon-three','balloon-four'].forEach(cls=>{const button=document.createElement('button');button.type='button';button.className='birthday-balloon '+cls;button.textContent='🎈';button.setAttribute('aria-label','Pop a birthday balloon');button.addEventListener('click',()=>{button.classList.add('popped');confettiBurst(.35,false);setTimeout(()=>button.remove(),350);});hero.appendChild(button);});
 }
 function completeStep(name){const item=document.querySelector('[data-trail-step="'+name+'"]');if(item&&!item.classList.contains('is-complete')){item.classList.add('is-complete');const n=item.querySelector('span');if(n)n.textContent='✓';}}
 const gallery=document.getElementById('birthdayMemories');
 if(gallery&&'IntersectionObserver'in window){const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting){completeStep('memories');observer.disconnect();}}),{threshold:.2});observer.observe(gallery);}
 const letter=document.getElementById('revealLetter');if(letter)letter.addEventListener('click',()=>{completeStep('letter');confettiBurst(.48,false);});
 const cake=document.getElementById('makeWishBtn');if(cake)cake.addEventListener('click',()=>{completeStep('cake');confettiBurst(.62,true);});
 const wishForm=document.getElementById('wishForm');if(wishForm)wishForm.addEventListener('submit',()=>{completeStep('wish');});
 const wishFeedback=document.getElementById('wishFeedback');
 if(wishFeedback&&window.MutationObserver){const observer=new MutationObserver(()=>{if(/pahunch gayi|thank/i.test(wishFeedback.textContent)){completeStep('wish');confettiBurst(.5,true);}});observer.observe(wishFeedback,{childList:true,subtree:true,characterData:true});}
 const finalButton=document.getElementById('celebrateAgain');if(finalButton)finalButton.addEventListener('click',()=>confettiBurst(.56,true));
 document.querySelectorAll('.birthday-hero__button').forEach(button=>button.addEventListener('click',()=>confettiBurst(.4,false)));
 if(config.trail){const board=document.getElementById('wishBoard');if(board&&'IntersectionObserver'in window){const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting)completeStep('wish');}),{threshold:.15});observer.observe(board);}}
})();
</script>
</body>
</html>