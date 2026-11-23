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
if ($shareImage === $publicBaseUrl . '/favicon.ico' && !empty($birthdayMedia[0]['file'])) {
    $firstMemory = 'birthday-media/' . basename((string)$birthdayMedia[0]['file']);
    if (preg_match('#^birthday-media/[a-f0-9]{32}\\.(?:jpg|jpeg|png|webp|gif)$#i', $firstMemory) && is_file(__DIR__ . '/' . $firstMemory)) {
        $shareImage = $publicBaseUrl . '/' . $firstMemory;
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
<meta property="og:image" content="<?= $escapeHtml($shareImage); ?>">\n<meta property="og:image:alt" content="<?= $escapeHtml($name); ?> birthday celebration">

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
.birthday-gallery{padding:30px 16px 18px}.birthday-section-heading{text-align:center;margin:0 auto 20px}.birthday-section-heading>span{font-size:30px}.birthday-section-heading h2{font-size:clamp(25px,5vw,34px);font-weight:850;color:#80543f;margin:5px 0}.birthday-section-heading p{margin:0;color:#81736a}.birthday-gallery__frame{width:100%;max-width:560px;height:clamp(300px,68svh,620px);min-height:280px;margin:0 auto;border-radius:28px;overflow:hidden;background:#f7e8d3;box-shadow:0 12px 35px #76533318;contain:layout paint}.birthday-gallery__frame .swiper-wrapper,.birthday-gallery__frame .swiper-slide{height:100%}.birthday-gallery__frame .swiper-slide{position:relative;display:flex;align-items:center;justify-content:center;background:#f7e8d3}.birthday-gallery__frame img,.birthday-gallery__frame video{display:block;width:100%;height:100%;max-width:100%;max-height:100%;object-fit:contain;object-position:center;background:#f7e8d3}.birthday-gallery__empty{max-width:560px;margin:auto;padding:40px 18px;border:2px dashed #f0d5ae;border-radius:26px;text-align:center;background:#fffdf8}.birthday-gallery__empty span{display:block;font-size:60px}.birthday-gallery__empty strong,.birthday-gallery__empty small{display:block}.birthday-gallery__empty strong{font-size:17px;color:#76563e}.birthday-gallery__empty small{margin-top:7px;color:#998777}
.birthday-surprise-section{padding:22px 15px}.surprise-card{max-width:650px;margin:auto;padding:28px 20px;border:1px solid #f1dfc7;border-radius:26px;background:#fffdf8;color:#51433c;box-shadow:0 10px 28px #8b643012;text-align:center}.surprise-card h2{font-size:clamp(24px,5vw,34px);font-weight:850;color:#80543f}.surprise-card p{color:#76685d;line-height:1.7}.surprise-kicker{color:#d47b53;font-size:12px;font-weight:850}.surprise-button{background:#ffb84d;color:#5d3d20;border-radius:999px;padding:14px 20px;min-height:48px;font-weight:850}.cake-illustration{font-size:72px}
.wish-invite-section{padding:30px 15px 44px;background:linear-gradient(180deg,#fff5e7,#fffdf8)}.wish-invite-card{max-width:620px;margin:auto;padding:clamp(20px,5vw,36px);border-radius:30px;background:#fff;border:2px solid #f3dfc4;box-shadow:0 16px 42px #9a704014}.wish-invite-kicker{text-align:center;color:#d17a4f;font-weight:850}.wish-invite-card h2{text-align:center;font-size:clamp(26px,5vw,38px);font-weight:900;color:#82543d;margin:8px 0}.wish-invite-description{text-align:center;color:#83756b;line-height:1.6;margin:0 auto 24px;max-width:480px}.wish-field-label{display:block;color:#655348;font-weight:850;font-size:15px;margin:18px 0 9px}.wish-field-label span{font-size:12px;color:#9a8c81;font-weight:500}.wish-input{display:block;width:100%;min-height:54px;padding:15px 17px;border:2px solid #f0dfca;border-radius:16px;background:#fffdf9;color:#4e4038;font-size:17px;outline:none;box-shadow:none}.wish-input:focus{border-color:#ffb84d;box-shadow:0 0 0 4px #ffb84d2b}.wish-message-input{min-height:116px;resize:vertical;line-height:1.55}
.wish-relationship-choices,.wish-suggestions{display:flex;flex-wrap:wrap;gap:9px}.wish-choice,.wish-suggestion{min-height:44px;padding:10px 14px;border:1.5px solid #f0dfca;border-radius:999px;background:#fffaf1;color:#765c48;font-size:14px;font-weight:750}.wish-choice.selected,.wish-suggestion.selected{background:#ffedc8;border-color:#e6a844;color:#69451e;box-shadow:0 0 0 2px #ffe4ad}.wish-suggestions{display:grid;grid-template-columns:1fr 1fr}.wish-suggestion{text-align:left;border-radius:15px;min-height:58px;line-height:1.35}.wish-privacy-note{text-align:center;color:#95867b;font-size:13px;line-height:1.5;margin:14px 0}.wish-send-button{display:block;width:100%;min-height:58px;padding:14px 20px;border-radius:17px;background:#ff806f;color:#fff;font-size:18px;font-weight:900;box-shadow:0 5px #e56d5f}.wish-send-button:disabled{opacity:.65;box-shadow:none}.wish-feedback{text-align:center;min-height:22px;margin:14px 0 0;color:#8a5b32;font-weight:750}
.birthday-public #wishBoard{gap:14px}.birthday-public #wishBoard article{background:#fffdf8!important;border:1px solid #f1dfc7;box-shadow:0 8px 22px #805b3010!important;border-radius:20px!important}.birthday-public #wishBoard article p{color:#64554a!important}.birthday-public footer{background:#fff3df!important;color:#806d5d!important;border-color:#f1dfc7!important}.birthday-public .btn{min-height:44px}.birthday-public .swiper-pagination-bullet-active{background:#ff806f!important}
@media(max-width:520px){.birthday-hero{padding-top:34px}.birthday-gallery__frame{height:clamp(260px,64svh,560px);min-height:260px}.wish-suggestions{grid-template-columns:1fr}.wish-invite-card{border-radius:23px}.birthday-hero__toys{letter-spacing:3px}.birthday-public .mt-6.flex{padding:0 12px 18px}.birthday-public .btn{flex:1 1 42%;font-size:13px}}
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


</body>
</html>