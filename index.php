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
.birthday-gallery{padding:30px 16px 18px}.birthday-section-heading{text-align:center;margin:0 auto 20px}.birthday-section-heading>span{font-size:30px}.birthday-section-heading h2{font-size:clamp(25px,5vw,34px);font-weight:850;color:#80543f;margin:5px 0}.birthday-section-heading p{margin:0;color:#81736a}.birthday-gallery__frame{width:100%;max-width:560px;height:min(68vh,620px);height:clamp(300px,68svh,620px);min-height:280px;margin:0 auto;border-radius:28px;overflow:hidden;background:#f7e8d3;box-shadow:0 12px 35px #76533318;contain:layout paint}.birthday-gallery__frame .swiper-wrapper,.birthday-gallery__frame .swiper-slide{height:100%}.birthday-gallery__frame .swiper-slide{position:relative;display:flex;align-items:center;justify-content:center;background:#f7e8d3}.birthday-gallery__frame img,.birthday-gallery__frame video{display:block;width:100%;height:100%;max-width:100%;max-height:100%;object-fit:contain;object-position:center;background:#f7e8d3}.birthday-gallery__empty{max-width:560px;margin:auto;padding:40px 18px;border:2px dashed #f0d5ae;border-radius:26px;text-align:center;background:#fffdf8}.birthday-gallery__empty span{display:block;font-size:60px}.birthday-gallery__empty strong,.birthday-gallery__empty small{display:block}.birthday-gallery__empty strong{font-size:17px;color:#76563e}.birthday-gallery__empty small{margin-top:7px;color:#998777}
.birthday-surprise-section{padding:22px 15px}.surprise-card{max-width:650px;margin:auto;padding:28px 20px;border:1px solid #f1dfc7;border-radius:26px;background:#fffdf8;color:#51433c;box-shadow:0 10px 28px #8b643012;text-align:center}.surprise-card h2{font-size:clamp(24px,5vw,34px);font-weight:850;color:#80543f}.surprise-card p{color:#76685d;line-height:1.7}.surprise-kicker{color:#d47b53;font-size:12px;font-weight:850}.surprise-button{background:#ffb84d;color:#5d3d20;border-radius:999px;padding:14px 20px;min-height:48px;font-weight:850}.cake-illustration{font-size:72px}
.wish-invite-section{padding:30px 15px 44px;background:linear-gradient(180deg,#fff5e7,#fffdf8)}.wish-invite-card{max-width:620px;margin:auto;padding:clamp(20px,5vw,36px);border-radius:30px;background:#fff;border:2px solid #f3dfc4;box-shadow:0 16px 42px #9a704014}.wish-invite-kicker{text-align:center;color:#d17a4f;font-weight:850}.wish-invite-card h2{text-align:center;font-size:clamp(26px,5vw,38px);font-weight:900;color:#82543d;margin:8px 0}.wish-invite-description{text-align:center;color:#83756b;line-height:1.6;margin:0 auto 24px;max-width:480px}.wish-field-label{display:block;color:#655348;font-weight:850;font-size:15px;margin:18px 0 9px}.wish-field-label span{font-size:12px;color:#9a8c81;font-weight:500}.wish-input{display:block;width:100%;min-height:54px;padding:15px 17px;border:2px solid #f0dfca;border-radius:16px;background:#fffdf9;color:#4e4038;font-size:17px;outline:none;box-shadow:none}.wish-input:focus{border-color:#ffb84d;box-shadow:0 0 0 4px #ffb84d2b}.wish-message-input{min-height:116px;resize:vertical;line-height:1.55}
.wish-relationship-choices,.wish-suggestions{display:flex;flex-wrap:wrap;gap:9px}.wish-choice,.wish-suggestion{min-height:44px;padding:10px 14px;border:1.5px solid #f0dfca;border-radius:999px;background:#fffaf1;color:#765c48;font-size:14px;font-weight:750}.wish-choice.selected,.wish-suggestion.selected{background:#ffedc8;border-color:#e6a844;color:#69451e;box-shadow:0 0 0 2px #ffe4ad}.wish-suggestions{display:grid;grid-template-columns:1fr 1fr}.wish-suggestion{text-align:left;border-radius:15px;min-height:58px;line-height:1.35}.wish-privacy-note{text-align:center;color:#95867b;font-size:13px;line-height:1.5;margin:14px 0}.wish-send-button{display:block;width:100%;min-height:58px;padding:14px 20px;border-radius:17px;background:#ff806f;color:#fff;font-size:18px;font-weight:900;box-shadow:0 5px #e56d5f}.wish-send-button:disabled{opacity:.65;box-shadow:none}.wish-feedback{text-align:center;min-height:22px;margin:14px 0 0;color:#8a5b32;font-weight:750}
.birthday-public #wishBoard{gap:14px}.birthday-public #wishBoard article{background:#fffdf8!important;border:1px solid #f1dfc7;box-shadow:0 8px 22px #805b3010!important;border-radius:20px!important}.birthday-public #wishBoard article p{color:#64554a!important}.birthday-public footer{background:#fff3df!important;color:#806d5d!important;border-color:#f1dfc7!important}.birthday-public .btn{min-height:44px}.birthday-public .swiper-pagination-bullet-active{background:#ff806f!important}
@media(max-width:520px){.birthday-hero{padding-top:34px}.birthday-gallery__frame{height:min(64vh,560px);height:clamp(260px,64svh,560px);min-height:260px}.wish-suggestions{grid-template-columns:1fr}.wish-invite-card{border-radius:23px}.birthday-hero__toys{letter-spacing:3px}.birthday-public .mt-6.flex{padding:0 12px 18px}.birthday-public .btn{flex:1 1 42%;font-size:13px}}
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
</head>
<body class="birthday-public text-gray-800" data-birthday-theme="<?= $escapeHtml($themePreset) ?>" style="--birthday-accent:<?= $escapeHtml($themeAccent) ?>">
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

  <!-- Vaibhav's little memory gallery -->
<section class="birthday-gallery" data-aos="fade-up" aria-label="Vaibhav ki yaadein">
  <div class="birthday-section-heading"><span>📸</span><h2>Vaibhav ki pyari yaadein</h2><p>Chhote-chhote pal, dher saari khushiyan!</p></div>
  <?php if ($featuredImages || $birthdayMedia): ?>
  <div class="swiper featured-swiper birthday-gallery__frame"><div class="swiper-wrapper">
    <?php foreach ($featuredImages as $img): ?><div class="swiper-slide"><img src="<?= $escapeHtml($img) ?>?v0.5" class="w-full h-full object-cover" alt="Vaibhav ki yaad" loading="lazy"></div><?php endforeach; ?>
    <?php foreach ($birthdayMedia as $memory): if (!is_array($memory) || empty($memory['file'])) continue; $mediaFile = basename((string)$memory['file']); $mediaUrl = 'birthday-media/' . rawurlencode($mediaFile); ?>
      <div class="swiper-slide"><?php if (($memory['type'] ?? '') === 'video'): ?><video src="<?= $escapeHtml($mediaUrl) ?>" class="w-full h-full object-cover" controls muted playsinline preload="metadata"></video><?php else: ?><img src="<?= $escapeHtml($mediaUrl) ?>" class="w-full h-full object-cover" alt="<?= $escapeHtml($memory['caption'] ?? 'Vaibhav ki yaad') ?>" loading="lazy"><?php endif; ?>
      <?php if (trim((string)($memory['caption'] ?? '')) !== ''): ?><div class="memory-caption-overlay"><?= $escapeHtml($memory['caption']); ?></div><?php endif; ?></div>
    <?php endforeach; ?>
  </div><div class="swiper-pagination"></div></div>
  <?php else: ?><div class="birthday-gallery__empty"><span>🧸</span><strong>Vaibhav ki photos jald hi yahan aayengi!</strong><small>Birthday Studio se photos aur videos add kar sakte hain.</small></div><?php endif; ?>
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
    <form id="wishForm" autocomplete="on">
      <label class="wish-field-label" for="wishSenderName">Aapka naam</label>
      <input id="wishSenderName" type="text" name="name" required maxlength="80" autocomplete="name" placeholder="<?= $escapeHtml($namePlaceholder); ?>" class="wish-input" />
      <div class="wish-field-label">Aap Vaibhav ke kya lagte hain? <span>(optional)</span></div>
      <div class="wish-relationship-choices" role="group" aria-label="Aapka rishta">
        <button type="button" class="wish-choice" data-relation="Mummy">Mummy</button><button type="button" class="wish-choice" data-relation="Papa">Papa</button>
        <button type="button" class="wish-choice" data-relation="Dada-Dadi">Dada-Dadi</button><button type="button" class="wish-choice" data-relation="Nana-Nani">Nana-Nani</button>
        <button type="button" class="wish-choice" data-relation="Mama-Mami">Mama-Mami</button><button type="button" class="wish-choice" data-relation="Chacha-Chachi">Chacha-Chachi</button>
        <button type="button" class="wish-choice" data-relation="Bua-Fufa">Bua-Fufa</button><button type="button" class="wish-choice" data-relation="Friend">Friend</button>
      </div>
      <input type="hidden" name="pahchan" id="wishRelation" value="">
      <div class="wish-field-label">Wish likhna mushkil hai? Neeche se ek chuniye 👇</div>
      <div class="wish-suggestions" role="group" aria-label="Ready-made birthday wishes">
        <button type="button" class="wish-suggestion" data-wish-suggestion="Happy Birthday Vaibhav! Hamesha haste-khelte raho. Dher saara pyaar! 🎂❤️">🎂 Simple wish</button>
        <button type="button" class="wish-suggestion" data-wish-suggestion="Janamdin mubarak ho hamare Kittu! Bhagwan tumhe hamesha khush rakhe. 🌟">🌟 Pyaar bhari wish</button>
        <button type="button" class="wish-suggestion" data-wish-suggestion="Happy 2nd Birthday, little champ! Khoob khelo, masti karo aur hamesha muskurate raho! 🧸🎈">🧸 Little champ</button>
        <button type="button" class="wish-suggestion" data-wish-suggestion="Vaibhav beta, tumhari hasi hamesha aise hi chamakti rahe. Bahut saara aashirwad aur pyaar! 💛">💛 Aashirwad</button>
      </div>
      <label class="wish-field-label" for="wishMessage">Aapki wish</label>
      <textarea id="wishMessage" name="message" required maxlength="1200" rows="3" class="wish-input wish-message-input">Happy Birthday Vaibhav! Hamesha haste-khelte raho. Dher saara pyaar! 🎂❤️</textarea>
      <p class="wish-privacy-note">Bas itna hi! Aapki wish Vaibhav ke birthday page par dikhai degi. ❤️</p>
      <button id="wishSubmitButton" type="submit" class="wish-send-button">💌 <?= $escapeHtml($submitLabel); ?></button>
      <p id="wishFeedback" class="wish-feedback" role="status" aria-live="polite"></p>
    </form>
  </div>
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
  <audio id="bgMusic" loop preload="none" volume="0.12"><source src="<?= $escapeHtml($musicSource); ?>" type="audio/mpeg"></audio>
  <?php endif; ?>
<!-- Share Buttons Section -->
<div class="mt-6 flex flex-wrap gap-3 justify-center items-center">
  <a href="https://wa.me/?text=<?= rawurlencode($shareMessage) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-success">📱 WhatsApp</a>
  <a href="https://twitter.com/intent/tweet?text=<?= rawurlencode($shareMessage) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-info text-white">Share on X</a>
  <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($shareUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">📘 Facebook</a>
  <a href="https://t.me/share/url?url=<?= rawurlencode($shareUrl) ?>&text=<?= rawurlencode($shareMessage) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">💬 Telegram</a>
  <button onclick="copyBirthdayLink()" class="btn btn-dark" type="button">🔗 Copy Link</button>
  <button onclick="shareGeneral()" class="btn btn-warning text-dark" type="button">📤 Share</button>
</div>

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

<?php if ($musicEnabled): ?>
<div class="birthday-sound-control" role="group" aria-label="Sound controls">
  <button id="musicControlBtn" type="button" aria-label="Turn birthday music on" aria-pressed="false">🔇 Sound off</button>
  <label for="musicVolume">Volume <input id="musicVolume" type="range" min="0" max="30" value="12" step="1"></label>
</div>
<?php endif; ?>


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
  <script>
      AOS.init();
    const lightbox = GLightbox({ selector: '.glightbox' });
    const confettiCanvas = document.getElementById("confetti-canvas");
    const confettiInstance = confetti.create(confettiCanvas, { resize: true });

    new Swiper(".featured-swiper", {
      loop: true,
      autoplay: { delay: 3000 },
      pagination: { el: ".swiper-pagination", clickable: true },
    });

    $('img.lazy').each(function () {
      const img = $(this);
      img.on('load', () => img.addClass('loaded')).attr('src', img.attr('src'));
    });

function loadWishes() {
  $.getJSON("wishes.json?ts=" + new Date().getTime(), function (data) {
    $("#wishBoard").html("");

    data.reverse().forEach((wish) => {
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
      const $date = $("<p>").addClass("text-xs text-gray-400 mt-1").text(time);
      $card.append($wishText, $byline, $date);
      $("#wishBoard").append($card);
    });

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
    const defaultWish="Happy Birthday Vaibhav! Hamesha haste-khelte raho. Dher saara pyaar! 🎂❤️";
    try{const saved=localStorage.getItem("vaibhavWishSenderName");if(saved&&!wishName.value)wishName.value=saved;}catch(e){}
    document.querySelectorAll("[data-wish-suggestion]").forEach(button=>button.addEventListener("click",()=>{wishMessage.value=button.dataset.wishSuggestion||defaultWish;document.querySelectorAll("[data-wish-suggestion]").forEach(item=>item.classList.toggle("selected",item===button));}));
    document.querySelectorAll("[data-relation]").forEach(button=>button.addEventListener("click",()=>{const relation=button.dataset.relation||"";wishRelation.value=wishRelation.value===relation?"":relation;document.querySelectorAll("[data-relation]").forEach(item=>item.classList.toggle("selected",item===button&&wishRelation.value!==""));}));
    wishForm.on("submit",function(e){e.preventDefault();if(!wishName.value.trim()){wishFeedback.textContent="Pehle apna naam likh dijiye.";wishName.focus();return;}if(!wishMessage.value.trim()){wishFeedback.textContent="Ek chhoti si wish chuniye ya likh dijiye.";wishMessage.focus();return;}
      wishSubmit.disabled=true;wishSubmit.textContent="⏳ Wish bheji ja rahi hai...";wishFeedback.textContent="Bas ek pal...";
      $.ajax({url:"submit_wish.php",method:"POST",data:wishForm.serialize(),dataType:"json"}).done(function(res){if(res.success){try{localStorage.setItem("vaibhavWishSenderName",wishName.value.trim());}catch(e){}wishMessage.value=defaultWish;wishRelation.value="";document.querySelectorAll("[data-relation], [data-wish-suggestion]").forEach(item=>item.classList.remove("selected"));wishFeedback.textContent="🎉 Aapki wish Vaibhav tak pahunch gayi! Shukriya ❤️";loadWishes();if(typeof confettiInstance==="function")confettiInstance({particleCount:120,spread:160});}else{wishFeedback.textContent=res.error||"Wish send nahi ho paayi. Dobara try kijiye.";}}).fail(function(xhr){wishFeedback.textContent=xhr.responseJSON&&xhr.responseJSON.error?xhr.responseJSON.error:"Internet ya server mein dikkat hai. Ek baar phir try kijiye.";}).always(function(){wishSubmit.disabled=false;wishSubmit.textContent="💌 <?= $escapeHtml($submitLabel); ?>";});
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
    if (typeof confettiInstance === 'function') confettiInstance({particleCount: 90, spread: 75, origin: {y: 0.68}});
  });
  const celebrateAgain = document.getElementById('celebrateAgain');
  if (celebrateAgain) celebrateAgain.addEventListener('click', () => {
    if (typeof confettiInstance === 'function') confettiInstance({particleCount: 110, spread: 95, origin: {y: 0.65}});
  });
  const music = document.getElementById("bgMusic");
  const musicBtn = document.getElementById("musicControlBtn");
  const musicVolume = document.getElementById("musicVolume");
  if (music && musicBtn && musicVolume) {
    music.volume = 0.12;
    const syncSoundControl = () => {
      const isPlaying = !music.paused && !music.muted && music.volume > 0;
      musicBtn.textContent = isPlaying ? "🔊 Sound on" : "🔇 Sound off";
      musicBtn.setAttribute("aria-pressed", isPlaying ? "true" : "false");
      musicBtn.setAttribute("aria-label", isPlaying ? "Turn birthday music off" : "Turn birthday music on");
      musicVolume.value = String(Math.round(music.volume * 100));
    };
    musicBtn.addEventListener("click", () => {
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
</body>
</html>