<?php

ini_set('log_errors', 1);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'] . '/auxiliaries/errors.log');
require_once $_SERVER['DOCUMENT_ROOT']. "/includes/bootstrap.php";

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
$experienceSettings = is_array($experienceConfig['experience'] ?? null) ? $experienceConfig['experience'] : [];
$themeSettings = is_array($experienceConfig['theme'] ?? null) ? $experienceConfig['theme'] : [];
$themePreset = in_array(($themeSettings['preset'] ?? 'rose'), ['rose','sage','lavender','sunset'], true) ? $themeSettings['preset'] : 'rose';
$themeAccent = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($themeSettings['accent'] ?? '')) ? $themeSettings['accent'] : '#b98990';
$wishBoardHeading = trim((string)($experienceSettings['wishBoardHeading'] ?? '')) ?: 'A little love from everyone';
$namePlaceholder = trim((string)($experienceSettings['namePlaceholder'] ?? '')) ?: 'Your name';
$relationshipPlaceholder = trim((string)($experienceSettings['relationshipPlaceholder'] ?? '')) ?: 'How do you know them? (optional)';
$messagePlaceholder = trim((string)($experienceSettings['messagePlaceholder'] ?? '')) ?: 'Write a warm wish or a favourite memory…';
$scrollHint = trim((string)($experienceSettings['scrollHint'] ?? '')) ?: 'Stay a little, leave a wish, and enjoy the memories.';
$submitLabel = trim((string)($experienceSettings['submitLabel'] ?? '')) ?: 'Send your wish';
$metaDescription = trim((string)($experienceSettings['metaDescription'] ?? '')) ?: "Celebrate {$name}'s special day. Share a personal wish and a favourite memory.";
$publicBaseUrl = $protocol . $host . rtrim($current_directory, '/');
$shareImage = !empty($birthdayMedia[0]['file'])
    ? $publicBaseUrl . '/birthday-media/' . rawurlencode(basename((string)$birthdayMedia[0]['file']))
    : $publicBaseUrl . '/favicon.ico';
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
$shareMessage = "🎉 {$name}’s Birthday Celebration! 🎂\n\nWish {$name} a very happy birthday and be part of the celebration! 🎈\nDrop your wish here 👉 {$shareUrl}\n\nLet’s make {$personPronouns['possessive']} day extra special ❤️";
$escapeHtml = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
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
</head>
<body class="text-gray-800" data-birthday-theme="<?= $escapeHtml($themePreset) ?>" style="--birthday-accent:<?= $escapeHtml($themeAccent) ?>">
 <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
  <!-- 🎉 Interactive Heading -->
  
  <header class="text-center py-16 bg-gradient-to-br from-pink-200 via-pink-50 to-white p-5" data-aos="zoom-in">
    <h1 class="text-5xl md:text-7xl font-black text-transparent bg-clip-text bg-gradient-to-r from-pink-600 to-red-400 heading-glow">
      🎂 <?= $escapeHtml($heroTitle); ?> 🎉
    </h1>
    <p class="mt-4 text-xl md:text-2xl text-gray-700 italic"><?= $escapeHtml($heroSubtitle); ?></p>
    <div class="mt-3 text-pink-500 animate-bounce"><?= $escapeHtml($scrollHint); ?></div>
  </header>

  <!-- 🎞️ Featured Carousel -->
<section class="h-[90vh] px-4 py-10" data-aos="fade-up">
  <div class="swiper featured-swiper h-full max-w-md mx-auto rounded-2xl overflow-hidden shadow-xl">
    <div class="swiper-wrapper">
      <?php foreach ($featuredImages as $img): ?>
        <div class="swiper-slide border">
          <img src="<?= $escapeHtml($img) ?>?v0.5" class="w-full h-full object-cover rounded-xl" alt="Featured memory" loading="lazy">
        </div>
      <?php endforeach; ?>
      <?php foreach ($birthdayMedia as $memory): if (!is_array($memory) || empty($memory['file'])) continue; $mediaFile = basename((string)$memory['file']); $mediaUrl = 'birthday-media/' . rawurlencode($mediaFile); ?>
        <div class="swiper-slide border">
          <?php if (($memory['type'] ?? '') === 'video'): ?>
            <video src="<?= $escapeHtml($mediaUrl) ?>" class="w-full h-full object-cover rounded-xl" controls muted playsinline preload="metadata"></video>
          <?php else: ?>
            <img src="<?= $escapeHtml($mediaUrl) ?>" class="w-full h-full object-cover rounded-xl" alt="<?= $escapeHtml($memory['caption'] ?? $memory['name'] ?? 'Birthday memory') ?>" loading="lazy">
          <?php endif; ?>
          <?php if (trim((string)($memory['caption'] ?? '')) !== ''): ?><div class="memory-caption-overlay"><?= $escapeHtml($memory['caption']); ?></div><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="swiper-pagination mt-4"></div>
  </div>
</section>

<?php if ($showLetter): ?>
<section class="birthday-surprise-section" id="secretLetterSection" data-aos="fade-up">
  <div class="surprise-card letter-card">
    <div class="surprise-kicker">A note from the heart</div>
    <h2><?= $escapeHtml($letterHeading); ?></h2>
    <button type="button" class="surprise-button" id="revealLetter" aria-expanded="false"><?= $escapeHtml($letterButton); ?></button>
    <div class="surprise-reveal" id="letterContent" hidden><p><?= nl2br($escapeHtml($letterMessage)); ?></p></div>
  </div>
</section>
<?php endif; ?>
<?php if ($showCake): ?>
<section class="birthday-surprise-section" id="interactiveCakeSection" data-aos="fade-up">
  <div class="surprise-card cake-card">
    <div class="surprise-kicker">A moment just for you</div>
    <h2><?= $escapeHtml($cakeHeading); ?></h2>
    <div class="cake-illustration" id="cakeIllustration" aria-hidden="true">🎂</div>
    <p id="cakeMessage" class="cake-message" hidden><?= $escapeHtml($cakeMessage); ?></p>
    <button type="button" class="surprise-button" id="makeWishBtn"><?= $escapeHtml($cakeButton); ?></button>
  </div>
</section>
<?php endif; ?>

 <!-- 😂 Funny Birthday Wish Form -->
<section class="px-4 py-16 bg-white shadow-inner" data-aos="fade-up">
  <div class="max-w-xl mx-auto text-center">
    <h2 class="text-3xl font-extrabold text-pink-600 mb-6"><?= $escapeHtml($wishFormHeading); ?></h2>
    <form id="wishForm" class="space-y-4">
      
      <p class="mb-6 text-sm text-gray-600"><?= $escapeHtml($wishFormDescription); ?></p>
      <!-- Name -->
      <input type="text" name="name" required maxlength="80"
        placeholder="<?= $escapeHtml($namePlaceholder); ?>" 
        class="w-full px-5 py-3 border border-pink-300 rounded-full shadow focus:outline-none focus:ring-2 focus:ring-pink-400" />

      <!-- Pahchan -->
      <input type="text" name="pahchan" maxlength="100"
        placeholder="<?= $escapeHtml($relationshipPlaceholder); ?>" 
        class="w-full px-5 py-3 border border-pink-100 rounded-full shadow-sm focus:outline-none focus:ring-1 focus:ring-pink-300" />

      <!-- Message -->
      <textarea name="message" required maxlength="1200"
        placeholder="<?= $escapeHtml($messagePlaceholder); ?>" 
        class="w-full px-5 py-3 border border-pink-300 rounded-3xl shadow focus:outline-none focus:ring-2 focus:ring-pink-400 resize-none"></textarea>

      <!-- Submit Button -->
      
<small><?= $escapeHtml($name); ?> ko aapki wish bhej di jayegi — chaliye <?= $escapeHtml($personPronouns["possessive"]); ?> din banate hain! 🎂</small>

      <button type="submit"
        class="bg-pink-600 hover:bg-pink-700 text-white font-bold px-8 py-3 rounded-full shadow-lg transform hover:scale-105 transition">
        <?= $escapeHtml($submitLabel); ?>
      </button>
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
    <div class="surprise-kicker">With all the good wishes</div>
    <h2><?= $escapeHtml($finalHeading); ?></h2>
    <p><?= $escapeHtml($finalMessage); ?></p>
    <button type="button" class="surprise-button" id="celebrateAgain">A little more celebration ✨</button>
  </div>
</section>
<?php endif; ?>

  <!-- 🎵 Background Music -->
  <?php if ($musicEnabled): ?>
  <audio id="bgMusic" loop><source src="<?= $escapeHtml($musicSource); ?>" type="audio/mpeg"></audio>
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
    navigator.clipboard.writeText(shareText).then(() => {
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
  #musicControlBtn {
    width: 42px;
    height: 42px;
    font-size: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
</style>

<!-- 🎵 Floating Music Control -->
<?php if ($musicEnabled): ?>
<div class="fixed bottom-4 right-3 z-50">
  <button id="musicControlBtn"
    class="w-10 h-10 rounded-full border border-gray-300 bg-white shadow-lg flex items-center justify-center hover:bg-gray-100 transition relative group"
    aria-label="Music Control">
    🎵
    <!-- Tooltip -->
    <span
      class="absolute right-full mr-2 bottom-1/2 translate-y-1/2 bg-black text-white text-xs rounded px-2 py-1 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap">
      Tap to play/pause 🎶
    </span>
  </button>
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

    // Play music only on first user click
function playMusicOnce() {
  const music = document.getElementById("bgMusic");
  if (music && music.paused) {
    music.play().catch(() => {});
    document.removeEventListener("click", playMusicOnce);
  }
}
document.addEventListener("click", playMusicOnce, { once: true });

// Toggle on TRIPLE click
let clickTimes = [];
document.addEventListener("click", function () {
  const now = Date.now();
  clickTimes.push(now);

  // Keep only last 3 clicks
  if (clickTimes.length > 3) clickTimes.shift();

  // Check if 3 clicks occurred within 600ms
  if (clickTimes.length === 3 && (clickTimes[2] - clickTimes[0] <= 600)) {
    toggleMusic();
    clickTimes = []; // reset
  }
});

function toggleMusic() {
  const music = document.getElementById("bgMusic");
  if (!music) return;
  if (music.paused) music.play();
  else music.pause();
}
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
        Please try again later 😁.
      </div>
    `);
  });
}



    $("#wishForm").on("submit", function (e) {
      e.preventDefault();
      const formData = $(this).serialize();
      $.post("submit_wish.php", formData, function (res) {
        if (res.success) {
          $("#wishForm")[0].reset();
          loadWishes();
          confettiInstance({ particleCount: 120, spread: 160 });
        } else {
          alert("Error: " + res.error);
        }
      }, "json");
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
    makeWishBtn.textContent = 'Your wish is on its way ✨';
    makeWishBtn.disabled = true;
    if (typeof confettiInstance === 'function') confettiInstance({particleCount: 90, spread: 75, origin: {y: 0.68}});
  });
  const celebrateAgain = document.getElementById('celebrateAgain');
  if (celebrateAgain) celebrateAgain.addEventListener('click', () => {
    if (typeof confettiInstance === 'function') confettiInstance({particleCount: 110, spread: 95, origin: {y: 0.65}});
  });
  const music = document.getElementById("bgMusic");
  const musicBtn = document.getElementById("musicControlBtn");

  if (music && musicBtn) {
    musicBtn.addEventListener("click", () => {
      if (music.paused) {
        music.play().catch(() => {});
        musicBtn.classList.replace("bg-white", "bg-green-100");
      } else {
        music.pause();
        musicBtn.classList.replace("bg-green-100", "bg-white");
      }
    });
  }
  </script>


</body>
</html>