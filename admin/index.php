<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/bootstrap.php";
if (($_SESSION['user_type'] ?? '') !== 'developer') { http_response_code(403); exit('Developer access required.'); }

if (empty($_SESSION['birthday_admin_csrf'])) {
    $_SESSION['birthday_admin_csrf'] = bin2hex(random_bytes(32));
}
if (empty($_SESSION['birthday_studio_csrf'])) {
    $_SESSION['birthday_studio_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['birthday_admin_csrf'];
$studioCsrf = $_SESSION['birthday_studio_csrf'];

$configPath = dirname(__DIR__) . '/experience.json';
$samplePath = dirname(__DIR__) . '/experience.example.json';
$config = json_decode((string)@file_get_contents(is_file($configPath) ? $configPath : $samplePath), true);
if (!is_array($config)) $config = [];

function admin_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function admin_v(array $source, string $key, string $fallback = ''): string {
    return trim((string)($source[$key] ?? $fallback));
}

$person = is_array($config['person'] ?? null) ? $config['person'] : [];
$sender = is_array($config['sender'] ?? null) ? $config['sender'] : [];
$experience = is_array($config['experience'] ?? null) ? $config['experience'] : [];
$theme = is_array($config['theme'] ?? null) ? $config['theme'] : [];
$modules = is_array($config['modules'] ?? null) ? $config['modules'] : [];
$celebration = is_array($config['celebration'] ?? null) ? $config['celebration'] : [];
$notifications = is_array($config['notifications'] ?? null) ? $config['notifications'] : [];

$mediaPath = dirname(__DIR__) . '/birthday-media/.media-index.json';
$media = is_file($mediaPath) ? json_decode((string)file_get_contents($mediaPath), true) : [];
if (!is_array($media)) $media = [];

$wishesPath = dirname(__DIR__) . '/wishes.json';
$wishes = is_file($wishesPath) ? json_decode((string)file_get_contents($wishesPath), true) : [];
if (!is_array($wishes)) $wishes = [];

$publicUrl = admin_v($experience, 'shareUrl');
$notice = (string)($_SESSION['birthday_admin_notice'] ?? '');
unset($_SESSION['birthday_admin_notice']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#f7f4ef">
<title>Birthday Experience Admin</title>
<link rel="stylesheet" href="../styles.css">
<style>
:root{--admin-bg:#f7f4ef;--admin-panel:#fffdf9;--admin-text:#4b4440;--admin-muted:#887f78;--admin-line:#e8dfd7;--admin-accent:#b98990}
*{box-sizing:border-box}.admin-body{margin:0;background:linear-gradient(145deg,#f8f5f0,#fbf8f4 48%,#f1ece6);color:var(--admin-text);font-family:"DM Sans",system-ui,sans-serif}.admin-top{position:sticky;top:0;z-index:20;height:72px;border-bottom:1px solid rgba(210,198,188,.6);background:rgba(250,248,244,.9);backdrop-filter:blur(16px);display:flex;align-items:center;justify-content:space-between;padding:0 28px}.admin-brand{display:flex;gap:11px;align-items:center}.admin-mark{width:35px;height:35px;border-radius:50%;display:grid;place-items:center;background:#efe1dc;color:#986f76}.admin-brand strong{font-family:Georgia,serif;font-size:18px}.admin-brand small{display:block;color:var(--admin-muted);font-size:11px;margin-top:2px}.admin-actions{display:flex;gap:9px;align-items:center}.admin-btn{display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--admin-line);border-radius:999px;padding:10px 16px;text-decoration:none;background:#fffaf5;color:#625954;font-weight:700;font-size:12px;cursor:pointer}.admin-btn.primary{background:#b98990;border-color:#b98990;color:#fff}.admin-shell{max-width:1240px;margin:auto;display:grid;grid-template-columns:225px minmax(0,1fr);min-height:calc(100vh - 72px)}.admin-side{padding:30px 16px;border-right:1px solid rgba(220,209,200,.65)}.admin-context{padding:15px;border:1px solid var(--admin-line);border-radius:19px;background:rgba(255,253,249,.75);margin-bottom:24px}.admin-context small{color:var(--admin-muted);font-size:10px;text-transform:uppercase;letter-spacing:.12em}.admin-context strong{display:block;font-family:Georgia,serif;font-size:18px;margin-top:5px}.admin-nav{display:grid;gap:6px}.admin-nav button{border:0;background:transparent;text-align:left;color:#8a817b;padding:12px 14px;border-radius:999px;font-weight:700;cursor:pointer}.admin-nav button.active{background:#efe5df;color:#584d49}.admin-help{margin-top:30px;padding:15px;border-radius:19px;background:#f0ebe4;color:#716761;font-size:11px;line-height:1.6}.admin-main{padding:48px 52px 100px}.admin-section{display:none}.admin-section.active{display:block;animation:adminRise .25s ease}.eyebrow{font-size:10px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:#a67c82;margin-bottom:12px}.admin-main h1,.admin-main h2{font-family:Georgia,serif;font-weight:600;color:#4b4440;letter-spacing:-.02em}.admin-main h1{font-size:43px;margin:0 0 10px}.admin-main h2{font-size:28px;margin:0 0 7px}.lead{max-width:700px;color:var(--admin-muted);line-height:1.75;margin:0 0 27px}.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:22px 0 28px}.stat{padding:18px;border:1px solid var(--admin-line);border-radius:20px;background:rgba(255,253,249,.78)}.stat strong{display:block;font-size:27px;font-family:Georgia,serif}.stat span{display:block;color:var(--admin-muted);font-size:11px;margin-top:4px}.admin-card{padding:24px;border:1px solid var(--admin-line);border-radius:24px;background:rgba(255,253,249,.8);box-shadow:0 12px 40px rgba(92,72,57,.045);margin:16px 0}.admin-card>p{color:var(--admin-muted);line-height:1.65;font-size:13px}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.field{margin:8px 0 13px}.field.full{grid-column:1/-1}.field label{display:block;color:#706760;font-size:11px;font-weight:800;margin-bottom:6px}.field input,.field textarea,.field select{width:100%;border:1px solid #e5dcd4;border-radius:14px;background:#fffaf5;color:#514944;padding:12px 13px;outline:none;font:inherit;font-size:13px}.field textarea{min-height:100px;resize:vertical;line-height:1.55}.field input:focus,.field textarea:focus,.field select:focus{border-color:#c89a9f;box-shadow:0 0 0 4px rgba(200,154,159,.12)}.field small{display:block;color:#958a83;font-size:10px;margin-top:5px}.save-row{display:flex;justify-content:flex-end;gap:10px;margin-top:18px}.section-note{padding:13px 15px;border:1px dashed #d9cbc1;border-radius:17px;background:#fbf6f0;color:#786d66;font-size:11px;line-height:1.55;margin:15px 0}.check-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.check{display:flex;gap:10px;align-items:flex-start;padding:13px;border:1px solid var(--admin-line);border-radius:16px;background:#fffaf5}.check input{margin-top:2px}.check strong{font-size:12px}.check small{display:block;color:var(--admin-muted);font-size:10px;margin-top:3px}.media-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:18px}.media-item{position:relative;border:1px solid var(--admin-line);border-radius:18px;overflow:hidden;background:#f2ece6}.media-item img,.media-item video{display:block;width:100%;aspect-ratio:1;object-fit:contain;object-position:center;background:#eee5d8}.media-meta{padding:10px}.media-meta strong{display:block;font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.media-meta small{display:block;color:var(--admin-muted);font-size:9px;margin-top:4px}.media-tools{display:flex;gap:6px;margin-top:8px}.media-tools a,.media-tools button{border:1px solid var(--admin-line);border-radius:999px;background:#fffaf5;color:#685d58;padding:6px 9px;font-size:10px;cursor:pointer;text-decoration:none}.dropzone{display:block;border:1px dashed #d7c8be;border-radius:22px;padding:30px;text-align:center;background:#fffaf5;cursor:pointer}.dropzone:hover{border-color:#c89a9f}.dropzone input{display:none}.dropzone strong{display:block;margin-bottom:5px}.dropzone small{color:var(--admin-muted)}.status{padding:11px 14px;border-radius:14px;background:#eef2eb;color:#60705c;font-size:11px;margin-bottom:18px}.danger{background:#f7ebe8;color:#8a5d5a}.public-link{display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:13px 15px;background:#f7f0ea;border:1px solid var(--admin-line);border-radius:16px;font-size:11px}.public-link code{color:#806a63;overflow-wrap:anywhere}.mobile-nav{display:none}@keyframes adminRise{from{opacity:0;transform:translateY(7px)}to{opacity:1;transform:none}}@media(max-width:900px){.admin-shell{display:block}.admin-side{display:none}.admin-main{padding:30px 20px 80px}.mobile-nav{display:flex;gap:7px;overflow:auto;padding:10px 16px;border-bottom:1px solid var(--admin-line);background:#faf7f3}.mobile-nav button{white-space:nowrap;border:1px solid var(--admin-line);background:#fffaf5;border-radius:999px;padding:9px 13px;color:#746a64;font-size:11px}.mobile-nav button.active{background:#efe5df;color:#584d49}.stats{grid-template-columns:repeat(2,1fr)}.form-grid{grid-template-columns:1fr}.field.full{grid-column:auto}.check-grid{grid-template-columns:1fr}.media-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.admin-top{padding:0 16px}.admin-actions .preview-label{display:none}}@media(max-width:480px){.admin-main{padding:25px 14px 65px}.admin-main h1{font-size:35px}.stats{gap:8px}.stat{padding:14px}.stat strong{font-size:23px}.media-grid{grid-template-columns:1fr 1fr}.admin-btn{padding:9px 12px}}
</style>
</head>
<body class="admin-body">
<header class="admin-top">
  <div class="admin-brand">
    <div class="admin-mark">🎂</div>
    <div><strong>Birthday Experience</strong><small>Administrative workspace</small></div>
  </div>
  <div class="admin-actions">
    <?php if ($publicUrl !== ''): ?><a class="admin-btn preview-label" href="<?= admin_h($publicUrl) ?>" target="_blank" rel="noopener">View Experience</a><?php endif; ?>
    <a class="admin-btn" href="../studio.php">Studio</a>
  </div>
</header>

<nav class="mobile-nav" id="mobileNav">
  <button data-section="overview" class="active">Overview</button>
  <button data-section="profile">Birthday Profile</button>
  <button data-section="experience">Experience</button>
  <button data-section="memories">Memories</button>
  <button data-section="wishes">Wishes</button>
  <button data-section="appearance">Appearance</button>
  <button data-section="celebration">Celebration</button>
  <button data-section="notifications">Email</button>
</nav>

<div class="admin-shell">
<aside class="admin-side">
  <div class="admin-context"><small>Current experience</small><strong><?= admin_h(admin_v($person,'name','Birthday person')); ?></strong></div>
  <nav class="admin-nav">
    <button data-section="overview" class="active">Overview</button>
    <button data-section="profile">Birthday Profile</button>
    <button data-section="experience">Experience</button>
    <button data-section="memories">Memories</button>
    <button data-section="wishes">Wishes</button>
    <button data-section="appearance">Appearance</button>
    <button data-section="celebration">Celebration</button>
    <button data-section="notifications">Email Notifications</button>
  </nav>
  <div class="admin-help"><strong>Administrative view</strong><br>Manage the birthday person’s details, memories, wishes and visitor experience from one place.</div>
</aside>

<main class="admin-main">
<?php if ($notice !== ''): ?><div class="status"><?= admin_h($notice) ?></div><?php endif; ?>

<section class="admin-section active" data-section-panel="overview">
  <div class="eyebrow">Birthday experience</div>
  <h1>Overview</h1>
  <p class="lead">A quick view of the current birthday experience and the items visitors can see.</p>
  <div class="stats">
    <div class="stat"><strong><?= count($media) ?></strong><span>Memories</span></div>
    <div class="stat"><strong><?= count($wishes) ?></strong><span>Wishes received</span></div>
    <div class="stat"><strong><?= $publicUrl !== '' ? 'Ready' : 'Draft' ?></strong><span>Public experience</span></div>
    <div class="stat"><strong><?= count(array_filter($modules, static fn($v) => $v === true)) ?></strong><span>Active moments</span></div>
  </div>
  <div class="admin-card">
    <div class="eyebrow">Quick actions</div>
    <h2>Keep the celebration up to date</h2>
    <p>Changes made here update the same experience used by visitors. Existing Studio functionality remains available for detailed editing and preview.</p>
    <div class="save-row">
      <button class="admin-btn primary" type="button" data-go="profile">Edit birthday profile</button>
      <button class="admin-btn" type="button" data-go="memories">Manage memories</button>
      <button class="admin-btn" type="button" data-go="notifications">Update email</button>
    </div>
  </div>
</section>

<section class="admin-section" data-section-panel="profile">
  <div class="eyebrow">Birthday person</div>
  <h1>Birthday Profile</h1>
  <p class="lead">Manage the birthday person’s name, nickname, birthday and the person sending the celebration.</p>
  <form method="post" action="save.php">
    <input type="hidden" name="csrf" value="<?= admin_h($csrf) ?>">
    <input type="hidden" name="section" value="profile">
    <div class="admin-card"><div class="form-grid">
      <div class="field"><label>Birthday person name</label><input name="person[name]" maxlength="80" required value="<?= admin_h(admin_v($person,'name')) ?>"></div>
      <div class="field"><label>Nickname</label><input name="person[nickname]" maxlength="80" value="<?= admin_h(admin_v($person,'nickname')) ?>"></div>
      <div class="field"><label>Birthday</label><input type="date" name="person[birthday]" value="<?= admin_h(admin_v($person,'birthday')) ?>"></div>
      <div class="field"><label>Pronouns</label><select name="person[pronoun]"><option value="he" <?= admin_v($person,'pronoun')==='he'?'selected':'' ?>>He / Him / His</option><option value="she" <?= admin_v($person,'pronoun')==='she'?'selected':'' ?>>She / Her</option><option value="they" <?= admin_v($person,'pronoun','they')==='they'?'selected':'' ?>>They / Them / Their</option></select></div>
      <div class="field"><label>Celebration sender name</label><input name="sender[name]" maxlength="80" value="<?= admin_h(admin_v($sender,'name')) ?>"></div>
    </div></div>
    <div class="save-row"><button class="admin-btn primary" type="submit">Save birthday profile</button></div>
  </form>
</section>

<section class="admin-section" data-section-panel="experience">
  <div class="eyebrow">Visitor experience</div>
  <h1>Experience</h1>
  <p class="lead">Control the words and interactive moments presented to visitors. Administrative labels remain in third-person context.</p>
  <form method="post" action="save.php">
    <input type="hidden" name="csrf" value="<?= admin_h($csrf) ?>"><input type="hidden" name="section" value="experience">
    <div class="admin-card"><h2>Hero</h2><div class="form-grid">
      <div class="field full"><label>Hero title</label><input name="experience[title]" maxlength="120" value="<?= admin_h(admin_v($experience,'title')) ?>"></div>
      <div class="field full"><label>Hero subtitle</label><textarea name="experience[subtitle]" maxlength="600"><?= admin_h(admin_v($experience,'subtitle')) ?></textarea></div>
      <div class="field full"><label>Scroll hint</label><input name="experience[scrollHint]" maxlength="140" value="<?= admin_h(admin_v($experience,'scrollHint')) ?>"></div>
      <div class="field full"><label>Meta description</label><textarea name="experience[metaDescription]" maxlength="220"><?= admin_h(admin_v($experience,'metaDescription')) ?></textarea></div>
      <div class="field full"><label>WhatsApp / social preview image</label><select name="experience[ogImage]"><option value="">Auto: first uploaded photo</option><?php foreach($media as $ogItem): if(!is_array($ogItem)||($ogItem['type']??'video')==='video'||empty($ogItem['file'])) continue; $ogFile=basename((string)$ogItem['file']); if(!preg_match('/^[a-f0-9]{32}\\.(jpg|jpeg|png|webp|gif)$/i',$ogFile)) continue; $ogValue='birthday-media/'.$ogFile; ?><option value="<?=admin_h($ogValue)?>" <?=admin_v($experience,'ogImage')===$ogValue?'selected':''?>><?=admin_h($ogItem['caption']??$ogItem['name']??$ogFile)?></option><?php endforeach; ?></select><small>Upload a photo in Memories first, then choose it here for WhatsApp and Facebook previews.</small></div>
      <div class="field full"><label>Public share URL</label><input type="url" name="experience[shareUrl]" maxlength="250" value="<?= admin_h(admin_v($experience,'shareUrl')) ?>"></div>
    </div></div>
    <div class="admin-card"><h2>Wishes</h2><div class="form-grid">
      <div class="field"><label>Wish form heading</label><input name="experience[formHeading]" maxlength="100" value="<?= admin_h(admin_v($experience,'formHeading')) ?>"></div>
      <div class="field"><label>Wish board heading</label><input name="experience[wishBoardHeading]" maxlength="100" value="<?= admin_h(admin_v($experience,'wishBoardHeading')) ?>"></div>
      <div class="field full"><label>Wish form description</label><textarea name="experience[formDescription]" maxlength="220"><?= admin_h(admin_v($experience,'formDescription')) ?></textarea></div>
      <div class="field"><label>Visitor name placeholder</label><input name="experience[namePlaceholder]" maxlength="100" value="<?= admin_h(admin_v($experience,'namePlaceholder')) ?>"></div>
      <div class="field"><label>Relationship placeholder</label><input name="experience[relationshipPlaceholder]" maxlength="140" value="<?= admin_h(admin_v($experience,'relationshipPlaceholder')) ?>"></div>
      <div class="field full"><label>Wish message placeholder</label><textarea name="experience[messagePlaceholder]" maxlength="180"><?= admin_h(admin_v($experience,'messagePlaceholder')) ?></textarea></div>
      <div class="field"><label>Submit button</label><input name="experience[submitLabel]" maxlength="70" value="<?= admin_h(admin_v($experience,'submitLabel')) ?>"></div>
    </div></div>
    <div class="admin-card"><h2>Interactive moments</h2><div class="check-grid">
      <label class="check"><input type="checkbox" name="modules[letter]" <?= !empty($modules['letter'])?'checked':'' ?>><span><strong>Secret letter</strong><small>Show the hidden personal note.</small></span></label>
      <label class="check"><input type="checkbox" name="modules[cake]" <?= !empty($modules['cake'])?'checked':'' ?>><span><strong>Wish-making cake</strong><small>Show the interactive birthday cake.</small></span></label>
      <label class="check"><input type="checkbox" name="modules[final]" <?= !empty($modules['final'])?'checked':'' ?>><span><strong>Final message</strong><small>Show the closing celebration message.</small></span></label>
      <label class="check"><input type="checkbox" name="modules[music]" <?= !empty($modules['music'])?'checked':'' ?>><span><strong>Background music</strong><small>Allow the configured birthday music.</small></span></label>
    </div></div>
    <div class="admin-card"><h2>Moment content</h2><div class="form-grid">
      <div class="field"><label>Letter heading</label><input name="experience[letterHeading]" maxlength="100" value="<?= admin_h(admin_v($experience,'letterHeading')) ?>"></div>
      <div class="field"><label>Letter button</label><input name="experience[letterButton]" maxlength="80" value="<?= admin_h(admin_v($experience,'letterButton')) ?>"></div>
      <div class="field full"><label>Letter message</label><textarea name="experience[letterMessage]" maxlength="1400"><?= admin_h(admin_v($experience,'letterMessage')) ?></textarea></div>
      <div class="field"><label>Cake heading</label><input name="experience[cakeHeading]" maxlength="100" value="<?= admin_h(admin_v($experience,'cakeHeading')) ?>"></div>
      <div class="field"><label>Cake button</label><input name="experience[cakeButton]" maxlength="80" value="<?= admin_h(admin_v($experience,'cakeButton')) ?>"></div>
      <div class="field full"><label>Cake message</label><textarea name="experience[cakeMessage]" maxlength="500"><?= admin_h(admin_v($experience,'cakeMessage')) ?></textarea></div>
      <div class="field"><label>Final heading</label><input name="experience[finalHeading]" maxlength="100" value="<?= admin_h(admin_v($experience,'finalHeading')) ?>"></div>
      <div class="field full"><label>Final message</label><textarea name="experience[finalMessage]" maxlength="600"><?= admin_h(admin_v($experience,'finalMessage')) ?></textarea></div>
      <div class="field full"><label>Custom music HTTPS URL</label><input type="url" name="experience[musicUrl]" maxlength="500" value="<?= admin_h(admin_v($experience,'musicUrl')) ?>"><small>Leave empty to use the bundled birthday song.</small></div>
    </div></div>
    <div class="save-row"><button class="admin-btn primary" type="submit">Save experience</button></div>
  </form>
</section>

<section class="admin-section" data-section-panel="memories">
  <div class="eyebrow">Media library</div>
  <h1>Memories</h1>
  <p class="lead">Manage photos, videos and audio recordings displayed in the birthday experience. Up to 30 memories, 25 MB per file.</p>
  <div class="admin-card">
    <form method="post" action="../upload-media.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= admin_h($studioCsrf) ?>"><input type="hidden" name="return_to" value="admin">
      <label class="dropzone"><input id="adminMediaFiles" type="file" name="media[]" accept="image/*,video/mp4,video/webm,video/quicktime,audio/mpeg,audio/mp4,audio/x-m4a,audio/wav,audio/x-wav,audio/ogg,audio/webm,audio/aac,audio/flac" multiple><strong>📸 Photos, videos ya audio choose karein</strong><small>Ek saath multiple files • har file 25 MB tak • total 30 memories</small><span id="adminUploadSelection" class="section-note">Abhi koi file select nahi hai</span></label>
      <div class="save-row"><button class="admin-btn primary" type="submit">Upload memories</button></div>
    </form>
    <?php if (!$media): ?><div class="section-note">No memories have been uploaded yet. Add the first photos or videos for visitors to discover.</div><?php else: ?>
    <div class="media-grid">
      <?php foreach ($media as $item): if (!is_array($item) || empty($item['file'])) continue; $file=basename((string)$item['file']); ?>
      <article class="media-item">
        <?php if (($item['type'] ?? '') === 'video'): ?><video src="../birthday-media/<?= admin_h(rawurlencode($file)) ?>" controls muted preload="metadata"></video><?php elseif (($item['type'] ?? '') === 'audio'): ?><div style="padding:14px;background:#f5eadc"><strong>🎵 Audio memory</strong><audio src="../birthday-media/<?= admin_h(rawurlencode($file)) ?>" controls preload="metadata" style="display:block;width:100%;margin-top:10px"></audio></div><?php else: ?><img src="../birthday-media/<?= admin_h(rawurlencode($file)) ?>" alt="<?= admin_h($item['caption'] ?? $item['name'] ?? 'Birthday memory') ?>" loading="lazy"><?php endif; ?>
        <div class="media-meta"><strong><?= admin_h($item['caption'] ?? $item['name'] ?? 'Birthday memory') ?></strong><small><?= admin_h($item['type'] ?? 'image') ?></small>
          <div class="media-tools"><button type="button" class="edit-caption" data-file="<?= admin_h($file) ?>" data-caption="<?= admin_h($item['caption'] ?? '') ?>">Edit caption</button><button type="button" class="delete-memory" data-file="<?= admin_h($file) ?>">Remove</button></div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="admin-section" data-section-panel="wishes">
  <div class="eyebrow">Visitor messages</div>
  <h1>Wishes</h1>
  <p class="lead">Review visitor wishes and play or open any photos, videos, voice messages and PDFs they attached.</p>
  <div class="admin-card">
  <?php if (!$wishes): ?><div class="section-note">No visitor wishes have been received yet.</div><?php else: ?>
    <?php foreach (array_reverse($wishes) as $wish): if (!is_array($wish)) continue; ?>
      <div style="padding:15px 0;border-bottom:1px solid var(--admin-line)">
        <strong style="font-size:13px"><?= admin_h($wish['name'] ?? 'Anonymous') ?></strong>
        <?php if (!empty($wish['pahchan'])): ?><span style="color:var(--admin-muted);font-size:10px"> • <?= admin_h($wish['pahchan']) ?></span><?php endif; ?>
        <p style="margin:6px 0;color:#6f655f;font-size:12px;line-height:1.6"><?= nl2br(admin_h($wish['message'] ?? '')) ?></p>
        <?php if (!empty($wish['media']) && is_array($wish['media'])): ?><div style="display:flex;flex-wrap:wrap;gap:10px;margin:10px 0">
          <?php foreach (array_slice($wish['media'],0,3) as $attachment): if (!is_array($attachment) || empty($attachment['file'])) continue; $attachmentFile=basename((string)$attachment['file']); if (!preg_match('/^[a-f0-9]{32}\\.(jpg|jpeg|png|webp|gif|mp4|webm|mov|mp3|m4a|wav|ogg|weba|aac|pdf)$/i',$attachmentFile)) continue; $attachmentUrl='../wish-media/'.rawurlencode($attachmentFile); ?>
            <?php if (($attachment['type'] ?? '') === 'image'): ?><a href="<?= admin_h($attachmentUrl) ?>" target="_blank" rel="noopener"><img src="<?= admin_h($attachmentUrl) ?>" alt="Wish attachment" style="width:130px;height:110px;object-fit:contain;border-radius:12px;background:#f2ece6"></a>
            <?php elseif (($attachment['type'] ?? '') === 'video'): ?><video src="<?= admin_h($attachmentUrl) ?>" controls preload="metadata" style="width:180px;max-height:130px;border-radius:12px"></video>
            <?php elseif (($attachment['type'] ?? '') === 'audio'): ?><audio src="<?= admin_h($attachmentUrl) ?>" controls preload="metadata" style="width:220px"></audio>
            <?php elseif (($attachment['type'] ?? '') === 'document'): ?><a class="admin-btn" href="<?= admin_h($attachmentUrl) ?>" target="_blank" rel="noopener">📄 Open PDF attachment</a><?php endif; ?>
          <?php endforeach; ?>
        </div><?php endif; ?>
        <?php if (!empty($wish['timestamp'])): ?><small style="color:#9a8f88;font-size:9px"><?= admin_h(date('d M Y, h:i A',(int)$wish['timestamp'])) ?></small><?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
  </div>
</section>

<section class="admin-section" data-section-panel="appearance">
  <div class="eyebrow">Visual identity</div>
  <h1>Appearance</h1>
  <p class="lead">Choose the visual tone used throughout the birthday experience.</p>
  <form method="post" action="save.php">
    <input type="hidden" name="csrf" value="<?= admin_h($csrf) ?>"><input type="hidden" name="section" value="appearance">
    <div class="admin-card"><div class="form-grid">
      <div class="field"><label>Theme preset</label><select name="theme[preset]"><?php foreach(['rose'=>'Rose','sage'=>'Sage','lavender'=>'Lavender','sunset'=>'Sunset','sunshine'=>'Sunshine party (kids)'] as $key=>$label): ?><option value="<?= $key ?>" <?= admin_v($theme,'preset','rose')===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Accent colour</label><input type="text" name="theme[accent]" pattern="#[0-9a-fA-F]{6}" maxlength="7" value="<?= admin_h(admin_v($theme,'accent','#b98990')) ?>"></div>
    </div></div>
    <div class="save-row"><button class="admin-btn primary" type="submit">Save appearance</button></div>
  </form>
</section>

<section class="admin-section" data-section-panel="celebration">
  <div class="eyebrow">Motion and engagement</div>
  <h1>Celebration</h1>
  <p class="lead">Fine-tune the interactive birthday moments, floating decorations, confetti celebrations and compact visitor controls. These settings affect the public birthday page only.</p>
  <form method="post" action="save.php">
    <input type="hidden" name="csrf" value="<?= admin_h($csrf) ?>">
    <input type="hidden" name="section" value="celebration">
    <div class="admin-card">
      <h2>Interactive atmosphere</h2>
      <p>Keep the page lively without overwhelming visitors or slowing down smaller devices.</p>
      <div class="check-grid">
        <label class="check"><input type="checkbox" name="celebration[confetti]" value="1" <?= ($celebration['confetti'] ?? true) !== false ? 'checked' : '' ?>><span><strong>Confetti and celebration bursts</strong><small>Celebrate page entry, cake wishes, sent wishes, balloons and the celebration button.</small></span></label>
        <label class="check"><input type="checkbox" name="celebration[floatingDecor]" value="1" <?= ($celebration['floatingDecor'] ?? true) !== false ? 'checked' : '' ?>><span><strong>Floating birthday decorations</strong><small>Show gently floating, tappable balloons and ambient decorative motion.</small></span></label>
        <label class="check"><input type="checkbox" name="celebration[celebrationTrail]" value="1" <?= ($celebration['celebrationTrail'] ?? true) !== false ? 'checked' : '' ?>><span><strong>Birthday journey guide</strong><small>Show quick links for memories, the letter, cake time and sending a wish.</small></span></label>
      </div>
    </div>
    <div class="admin-card">
      <h2>Floating controls</h2>
      <p>The sound and celebration controls stay tucked into one small launcher. Visitors tap it to open the panel; it closes itself after inactivity.</p>
      <div class="form-grid">
        <div class="field">
          <label>Auto-hide delay (seconds)</label>
          <input type="number" name="celebration[controlsAutoHideSeconds]" min="3" max="15" step="1" value="<?= admin_h((string)max(3,min(15,(int)($celebration['controlsAutoHideSeconds'] ?? 5)))) ?>">
          <small>Choose 3–15 seconds. The timer restarts when a visitor interacts with the panel.</small>
        </div>
      </div>
      <div class="section-note">Accessibility: reduced-motion preferences automatically disable continuous animation and confetti. The floating controls remain keyboard accessible and can be closed with Escape.</div>
    </div>
    <div class="save-row"><button class="admin-btn primary" type="submit">Save celebration settings</button></div>
  </form>
</section>

<section class="admin-section" data-section-panel="notifications">
  <div class="eyebrow">Wish notifications</div>
  <h1>Email Notifications</h1>
  <p class="lead">Choose where new visitor wishes are delivered and which sender identity is used for the notification.</p>
  <form method="post" action="save.php">
    <input type="hidden" name="csrf" value="<?= admin_h($csrf) ?>"><input type="hidden" name="section" value="notifications">
    <div class="admin-card"><div class="form-grid">
      <div class="field full"><label>Primary recipient email</label><input type="email" name="notifications[to]" maxlength="180" value="<?= admin_h(admin_v($notifications,'to')) ?>" required><small>New wishes are sent here.</small></div>
      <div class="field"><label>CC email(s)</label><input type="text" name="notifications[cc]" maxlength="500" value="<?= admin_h(admin_v($notifications,'cc')) ?>"><small>Optional, comma-separated.</small></div>
      <div class="field"><label>BCC email(s)</label><input type="text" name="notifications[bcc]" maxlength="500" value="<?= admin_h(admin_v($notifications,'bcc')) ?>"><small>Optional, comma-separated.</small></div>
      <div class="field"><label>From name</label><input name="notifications[fromName]" maxlength="80" value="<?= admin_h(admin_v($notifications,'fromName','Birthday Bot')) ?>"></div>
      <div class="field"><label>From email</label><input type="email" name="notifications[fromEmail]" maxlength="180" value="<?= admin_h(admin_v($notifications,'fromEmail')) ?>"><small>Use a mailbox permitted by the hosting mail configuration.</small></div>
    </div>
    <div class="section-note">Email addresses are stored in the untracked experience configuration, not in the public PHP source. The notification handler will not expose them on the public page.</div>
    </div>
    <div class="save-row"><button class="admin-btn primary" type="submit">Save email settings</button></div>
  </form>
</section>
</main>
</div>

<script>
(function(){
 const buttons=[...document.querySelectorAll('[data-section]')];
 const panels=[...document.querySelectorAll('[data-section-panel]')];
 function show(section){
   buttons.forEach(b=>b.classList.toggle('active',b.dataset.section===section));
   panels.forEach(p=>p.classList.toggle('active',p.dataset.sectionPanel===section));
   history.replaceState(null,'','#'+section);
   window.scrollTo({top:0,behavior:'smooth'});
 }
 buttons.forEach(b=>b.addEventListener('click',()=>show(b.dataset.section)));
 document.querySelectorAll('[data-go]').forEach(b=>b.addEventListener('click',()=>show(b.dataset.go)));
 const initial=location.hash.replace('#','');
 if(initial && panels.some(p=>p.dataset.sectionPanel===initial)) show(initial);

 const csrf=<?= json_encode($csrf) ?>;
 const mediaInput=document.getElementById('adminMediaFiles');
 if(mediaInput){const info=document.getElementById('adminUploadSelection');const form=mediaInput.closest('form');const submit=form.querySelector('button[type=submit]');submit.disabled=true;mediaInput.addEventListener('change',()=>{const chosen=Array.from(mediaInput.files||[]);const tooLarge=chosen.some(file=>file.size>25*1024*1024);info.textContent=chosen.length?chosen.length+' file(s) selected'+(tooLarge?' · Har file 25 MB se chhoti honi chahiye':''):'Abhi koi file select nahi hai';submit.disabled=!chosen.length||tooLarge;});form.addEventListener('submit',event=>{if(!mediaInput.files.length||Array.from(mediaInput.files).some(file=>file.size>25*1024*1024)){event.preventDefault();info.textContent='Photos/videos choose karein; har file 25 MB se chhoti honi chahiye.';return;}submit.disabled=true;submit.textContent='Uploading…';});}
 document.querySelectorAll('.edit-caption').forEach(btn=>{
   btn.addEventListener('click',async()=>{
     const caption=prompt('Memory caption:',btn.dataset.caption||'');
     if(caption===null)return;
     const fd=new FormData();fd.append('csrf',csrf);fd.append('file',btn.dataset.file);fd.append('caption',caption);
     const r=await fetch('../update-media.php',{method:'POST',body:fd});const j=await r.json();
     if(j.success){location.reload();}else{alert(j.error||'Could not update the caption.');}
   });
 });
 document.querySelectorAll('.delete-memory').forEach(btn=>{
   btn.addEventListener('click',async()=>{
     if(!confirm('Remove this memory from the birthday experience?'))return;
     const fd=new FormData();fd.append('csrf',csrf);fd.append('file',btn.dataset.file);
     const r=await fetch('../delete-media.php',{method:'POST',body:fd});const j=await r.json();
     if(j.success){location.reload();}else{alert(j.error||'Could not remove the memory.');}
   });
 });
})();
</script>
</body>
</html>
