<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/bootstrap.php";
if (($_SESSION['user_type'] ?? '') !== 'developer') { http_response_code(403); exit('Developer access required.'); }
if (empty($_SESSION['birthday_studio_csrf'])) $_SESSION['birthday_studio_csrf'] = bin2hex(random_bytes(32));
$configPath=__DIR__.'/experience.json'; $samplePath=__DIR__.'/experience.example.json';
$config=json_decode((string)@file_get_contents(is_file($configPath)?$configPath:$samplePath),true);
if(!is_array($config)) $config=[];
function studio_h($v): string { return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
$p=$config['person']??[]; $s=$config['sender']??[]; $x=$config['experience']??[]; $theme=$config['theme']??[]; $modules=$config['modules']??[];
$mediaPath=__DIR__.'/birthday-media/.media-index.json';
$media=is_file($mediaPath)?json_decode((string)file_get_contents($mediaPath),true):[];
if(!is_array($media)) $media=[];
$previewMedia=[];
foreach($media as $memoryItem){
 if(!is_array($memoryItem)||empty($memoryItem['file']))continue;
 $safeFile=basename((string)$memoryItem['file']);
 if(!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp|gif|mp4|webm|mov)$/',$safeFile))continue;
 $previewMedia[]=['url'=>'birthday-media/'.rawurlencode($safeFile),'type'=>($memoryItem['type']??'')==='video'?'video':'image'];
}
$csrf=$_SESSION['birthday_studio_csrf'];
$mediaNotice=(string)($_SESSION['birthday_media_notice']??'');unset($_SESSION['birthday_media_notice']);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#f7f4ef"><title>Birthday Studio</title><link rel="stylesheet" href="styles.css"><style>
.studio{max-width:1120px;margin:auto;padding:38px 22px 100px}.studio-head{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;margin-bottom:28px}.studio-head h1{font:600 clamp(36px,6vw,54px) Georgia,serif;color:#4b4440;margin:0}.studio-head p{max-width:610px;color:#887f78;line-height:1.7}.studio-panel{background:#fffdf9;border:1px solid #e9e1d9;border-radius:24px;padding:clamp(20px,3vw,30px);margin:18px 0;box-shadow:0 12px 38px #5b44300a}.studio-panel h2{font:600 25px Georgia,serif;color:#4b4440;margin:0 0 7px}.studio-panel>p{color:#887f78;line-height:1.65;margin:0 0 18px}.studio-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.studio-field{display:block;margin:12px 0;color:#706760;font-size:13px;font-weight:600}.studio-field input,.studio-field textarea,.studio-field select{display:block;width:100%;margin-top:7px;padding:13px;border:1px solid #e6ddd5;border-radius:14px;background:#fffaf5;color:#433d3a}.studio-field textarea{min-height:105px}.studio-drop{display:block;border:1.5px dashed #d8c9be;border-radius:20px;background:#fffaf5;text-align:center;padding:28px 18px;cursor:pointer;transition:.2s}.studio-drop.drag{background:#f7e9e5;border-color:#c89a9f}.studio-drop strong{display:block;color:#665652;margin:7px}.studio-drop small{color:#95877f}.studio-drop input{display:none}.media-library{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px;margin-top:18px}.library-item{position:relative;border:1px solid #eadfd7;border-radius:17px;overflow:hidden;background:#f7f2ec;min-width:0}.library-item img,.library-item video{width:100%;aspect-ratio:4/3;object-fit:contain;object-position:center;background:#eee5d8;display:block}.library-item .library-caption{padding:9px 10px;font-size:11px;color:#71655f;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.library-item button{position:absolute;right:8px;top:8px;background:#fffdf0ef;color:#7b555a;border:1px solid #e9ddd5;border-radius:50%;width:30px;height:30px}.media-empty{padding:25px;border:1px dashed #e3d8d0;border-radius:18px;text-align:center;color:#938780;grid-column:1/-1}.studio-preview{display:grid;grid-template-columns:minmax(0,1fr) 220px;gap:22px;align-items:center;padding:22px;border-radius:22px;background:linear-gradient(140deg,#f0dfdc,#fffaf3 65%,#e9eee6)}.studio-preview h3{font:600 clamp(27px,4vw,38px) Georgia,serif;color:#514541;margin:10px 0}.studio-preview p{color:#756b65;line-height:1.65}.preview-media{width:100%;aspect-ratio:4/3;border-radius:16px;overflow:hidden;background:#e8ded7;display:grid;place-items:center;color:#a18e85}.preview-media img,.preview-media video{width:100%;height:100%;object-fit:contain;object-position:center;background:#eee5d8}.studio-actions{display:flex;justify-content:flex-end;gap:12px;position:sticky;bottom:12px;padding:13px;background:#f7f4efdf;backdrop-filter:blur(12px);border:1px solid #e9e1d9;border-radius:18px;z-index:5}.studio-alert{padding:12px 15px;border-radius:13px;background:#eef3eb;color:#53674e;margin-bottom:15px}.studio-muted{font-size:12px;color:#95877f}
@media(max-width:760px){.studio-head{display:block}.studio-grid{grid-template-columns:1fr}.media-library{grid-template-columns:repeat(2,minmax(0,1fr))}.studio-preview{grid-template-columns:1fr}.preview-media{max-width:260px;margin:auto}.studio{padding:25px 14px 80px}}
.preview-panel{overflow:hidden}.preview-heading{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:18px}.preview-heading h2{margin:0 0 6px}.preview-heading p{margin:0;color:#887f78;font-size:13px}.preview-modes{display:flex;gap:6px;padding:4px;border-radius:999px;background:#f3ece6}.preview-mode{border:0;background:transparent;color:#82766f;border-radius:999px;padding:8px 13px;font-size:11px;font-weight:700}.preview-mode.active{background:#fffdf9;color:#665052;box-shadow:0 2px 8px #65504412}.preview-stage{display:flex;justify-content:center;align-items:flex-start;padding:12px;border-radius:22px;background:#eee8e1;min-height:390px;overflow:auto}.preview-stage iframe{width:100%;height:720px;border:0;border-radius:16px;background:#faf7f2;box-shadow:0 12px 30px #5d48301a;transition:width .25s;flex:0 0 auto}.preview-stage.phone iframe{width:390px;max-width:100%;height:760px}@media(max-width:600px){.preview-heading{align-items:flex-start;flex-direction:column}.preview-stage{padding:7px;min-height:320px}.preview-stage iframe{height:650px}.preview-stage.phone iframe{height:700px}}
.autofill-row{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:13px 15px;margin:16px 0 20px;border-radius:16px;background:#f8f1ec;border:1px solid #eee2da}.autofill-row span{font-size:12px;color:#82766f;line-height:1.5}.autofill-button{flex:0 0 auto;border:1px solid #e4d3cb;border-radius:999px;padding:9px 14px;background:#fffdf9;color:#8b656b;font-size:11px;font-weight:700}.autofill-button:hover{background:#f3e5e0}@media(max-width:560px){.autofill-row{align-items:flex-start;flex-direction:column}.autofill-button{width:100%}}
.module-switches{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:18px 0 22px}.module-choice{display:flex;align-items:flex-start;gap:11px;padding:14px;border:1px solid #eadfd7;border-radius:16px;background:#fffaf5;cursor:pointer}.module-choice input{width:17px;height:17px;flex:0 0 auto;margin:2px 0 0;accent-color:#b98990}.module-choice strong,.module-choice small{display:block}.module-choice strong{font-size:12px;color:#665652}.module-choice small{font-size:11px;line-height:1.5;color:#948780;margin-top:4px}.studio-full{grid-column:1/-1}@media(max-width:650px){.module-switches{grid-template-columns:1fr}}
.library-caption{display:block;padding:7px}.library-caption input{margin:0!important;width:100%;min-width:0;padding:8px 9px!important;border:1px solid #eadfd7!important;border-radius:10px!important;background:#fffdf9!important;color:#665b55!important;font-size:11px!important}.media-actions{position:absolute;top:7px;right:7px;display:flex;gap:4px}.media-actions button{position:static!important;width:27px!important;height:27px!important;display:grid;place-items:center;background:#fffdf0ef!important;color:#765f5c!important;border:1px solid #e9ddd5!important;border-radius:50%!important;font-size:13px}.media-actions .delete-media{color:#a35f68!important}.library-item{cursor:grab}.library-item:active{cursor:grabbing}
.studio{max-width:1440px}.studio-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(360px,470px);gap:22px;align-items:start}.studio-editor-column{min-width:0}.studio-preview-column{position:sticky;top:18px;min-width:0}.studio-preview-column .preview-panel{margin-top:0}.studio-preview-column .preview-stage{min-height:360px}.studio-preview-column .preview-stage iframe{height:min(76vh,850px)}.studio-preview-column .preview-stage.phone iframe{height:min(78vh,900px)}@media(max-width:980px){.studio-layout{grid-template-columns:minmax(0,1fr) minmax(300px,390px);gap:14px}.studio-preview-column .preview-heading{align-items:flex-start;flex-direction:column}}@media(max-width:760px){.studio-layout{grid-template-columns:1fr}.studio-preview-column{position:static}.studio-preview-column .preview-stage iframe{height:650px}.studio-preview-column .preview-stage.phone iframe{height:700px}}
</style></head><body><main class="studio">
<header class="studio-head"><div><div class="eyebrow">A little place to make it personal</div><h1>Birthday Studio</h1><p>Shape a celebration that feels personal. Add the details, gather the memories and watch the preview change as you write.</p></div><a class="ghost" href="index.php">View birthday page ↗</a></header>
<?php if(isset($_GET['saved'])): ?><div class="studio-alert">Your celebration details have been saved.</div><?php endif; ?>
<?php if(isset($_GET['uploaded'])): ?><div class="studio-alert"><?= (int)$_GET['uploaded'] ?> memory item(s) added to your celebration.</div><?php endif; ?>
<?php if($mediaNotice!==''): ?><div class="studio-alert" style="background:#fbf0e8;color:#865f4f"><?=studio_h($mediaNotice)?></div><?php endif; ?>
<div class="studio-layout"><div class="studio-editor-column">
<form id="experienceForm" method="post" action="save-experience.php"><input type="hidden" name="csrf" value="<?=studio_h($csrf)?>">
<section class="studio-panel"><h2>Who are we celebrating?</h2><p>Start with the person at the heart of this celebration.</p><div class="studio-grid">
<label class="studio-field">Their name<input name="person[name]" required maxlength="80" value="<?=studio_h($p['name']??'')?>"></label>
<label class="studio-field">A name you call them<input name="person[nickname]" maxlength="80" value="<?=studio_h($p['nickname']??'')?>" placeholder="Optional nickname"></label>
<label class="studio-field">Birthday<input type="date" name="person[birthday]" value="<?=studio_h($p['birthday']??'')?>"></label>
<label class="studio-field">Pronouns<select name="person[pronoun]"><option value="they" <?=($p['pronoun']??'they')==='they'?'selected':''?>>They / them</option><option value="she" <?=($p['pronoun']??'')==='she'?'selected':''?>>She / her</option><option value="he" <?=($p['pronoun']??'')==='he'?'selected':''?>>He / him</option></select></label>
<label class="studio-field">From<input name="sender[name]" maxlength="80" value="<?=studio_h($s['name']??'')?>" placeholder="Your name"></label></div></section>
<section class="studio-panel"><h2>Tell the story</h2><p>Choose words that sound like you. The preview updates as you type.</p><div class="autofill-row"><span>Need a starting point? Create a gentle draft using the name and pronouns above.</span><button type="button" id="autofillStory" class="autofill-button">✦ Create a starter</button></div>
<label class="studio-field">Celebration heading<input name="experience[title]" maxlength="120" value="<?=studio_h($x['title']??'A day made just for you')?>"></label>
<label class="studio-field">Opening message<textarea name="experience[subtitle]" maxlength="600"><?=studio_h($x['subtitle']??'A little celebration filled with love, laughter and memories.')?></textarea></label>
<div class="studio-grid"><label class="studio-field">Wish section heading<input name="experience[formHeading]" maxlength="100" value="<?=studio_h($x['formHeading']??'Leave a little happiness')?>"></label><label class="studio-field">Wish section note<input name="experience[formDescription]" maxlength="220" value="<?=studio_h($x['formDescription']??'Share a note, a memory or a wish.')?>"></label></div>
<label class="studio-field">Share page address<input type="url" name="experience[shareUrl]" maxlength="250" value="<?=studio_h($x['shareUrl']??'')?>" placeholder="https://your-domain.com/birthday"></label><div class="studio-grid">
<label class="studio-field">Wishes section heading<input name="experience[wishBoardHeading]" maxlength="100" value="<?=studio_h($x['wishBoardHeading']??'A little love from everyone')?>"></label>
<label class="studio-field">Name field hint<input name="experience[namePlaceholder]" maxlength="100" value="<?=studio_h($x['namePlaceholder']??'Your name')?>"></label>
<label class="studio-field">Relationship hint<input name="experience[relationshipPlaceholder]" maxlength="140" value="<?=studio_h($x['relationshipPlaceholder']??'How do you know them? (optional)')?>"></label>
<label class="studio-field">Wish message hint<input name="experience[messagePlaceholder]" maxlength="180" value="<?=studio_h($x['messagePlaceholder']??'Write a warm wish or a favourite memory…')?>"></label>
<label class="studio-field">Wish button label<input name="experience[submitLabel]" maxlength="70" value="<?=studio_h($x['submitLabel']??'Send your wish')?>"></label>
<label class="studio-field">Scroll hint<input name="experience[scrollHint]" maxlength="140" value="<?=studio_h($x['scrollHint']??'Stay a little, leave a wish, and enjoy the memories.')?>"></label>
<label class="studio-field">Search/social description<input name="experience[metaDescription]" maxlength="220" value="<?=studio_h($x['metaDescription']??'')?>"></label>
<label class="studio-field">WhatsApp / social preview image
<select name="experience[ogImage]">
<option value="">Auto: first uploaded photo</option>
<?php foreach($media as $ogItem): if(!is_array($ogItem)||($ogItem['type']??'video')==='video'||empty($ogItem['file'])) continue; $ogFile=basename((string)$ogItem['file']); if(!preg_match('/^[a-f0-9]{32}\\.(jpg|jpeg|png|webp|gif)$/i',$ogFile)) continue; $ogValue='birthday-media/'.$ogFile; ?>
<option value="<?=studio_h($ogValue)?>" <?=($x['ogImage']??'')===$ogValue?'selected':''?>><?=studio_h($ogItem['caption']??$ogItem['name']??$ogFile)?></option>
<?php endforeach; ?>
</select><small>Photo pehle Memories section mein upload karein. Yahan se choose karne par WhatsApp/Facebook preview mein yehi photo aayegi.</small></label>
</div></section><section class="studio-panel"><h2>Set the atmosphere</h2><p>Choose a gentle colour mood and a matching accent.</p><div class="studio-grid">
<label class="studio-field">Colour mood<select name="theme[preset]">
<option value="rose" <?=($theme['preset']??'rose')==='rose'?'selected':''?>>Soft rose</option>
<option value="sage" <?=($theme['preset']??'')==='sage'?'selected':''?>>Quiet sage</option>
<option value="lavender" <?=($theme['preset']??'')==='lavender'?'selected':''?>>Lavender dusk</option>
<option value="sunset" <?=($theme['preset']??'')==='sunset'?'selected':''?>>Warm sunset</option><option value="sunshine" <?=($theme['preset']??'')==='sunshine'?'selected':''?>>Sunshine party (kids)</option>
</select></label>
<label class="studio-field">Accent colour<input type="color" name="theme[accent]" value="<?=studio_h($theme['accent']??'#b98990')?>"></label>
</div><section class="studio-panel"><h2>Add a little surprise</h2><p>Choose which moments appear on the public birthday page, then write the words behind each one.</p>
<div class="module-switches">
<label class="module-choice"><input type="checkbox" name="modules[letter]" value="1" <?=($modules['letter']??true)!==false?'checked':''?>><span><strong>Secret letter</strong><small>A personal note revealed when the visitor opens it.</small></span></label>
<label class="module-choice"><input type="checkbox" name="modules[cake]" value="1" <?=($modules['cake']??true)!==false?'checked':''?>><span><strong>Wish-making cake</strong><small>An interactive candle moment with a gentle celebration.</small></span></label>
<label class="module-choice"><input type="checkbox" name="modules[final]" value="1" <?=($modules['final']??true)!==false?'checked':''?>><span><strong>Final message</strong><small>A closing note and a final celebration button.</small></span></label>
<label class="module-choice"><input type="checkbox" name="modules[music]" value="1" <?=($modules['music']??true)!==false?'checked':''?>><span><strong>Background music</strong><small>Keep the existing birthday song or use an HTTPS audio URL.</small></span></label>
</div>
<div class="studio-grid">
<label class="studio-field">Letter heading<input name="experience[letterHeading]" maxlength="100" value="<?=studio_h($x['letterHeading']??'A little note for you')?>"></label>
<label class="studio-field">Letter button<input name="experience[letterButton]" maxlength="80" value="<?=studio_h($x['letterButton']??'Open your little letter')?>"></label>
<label class="studio-field studio-full">Letter message<textarea name="experience[letterMessage]" maxlength="1400"><?=studio_h($x['letterMessage']??'Some people make ordinary days feel special. Today is a chance to celebrate everything that makes you wonderful.')?></textarea></label>
<label class="studio-field">Cake heading<input name="experience[cakeHeading]" maxlength="100" value="<?=studio_h($x['cakeHeading']??'Make a little wish')?>"></label>
<label class="studio-field">Cake button<input name="experience[cakeButton]" maxlength="80" value="<?=studio_h($x['cakeButton']??'Blow out the candles')?>"></label>
<label class="studio-field studio-full">Cake message<textarea name="experience[cakeMessage]" maxlength="500"><?=studio_h($x['cakeMessage']??'Take a breath, make a wish, and keep a little room for magic.')?></textarea></label>
<label class="studio-field">Final heading<input name="experience[finalHeading]" maxlength="100" value="<?=studio_h($x['finalHeading']??'Here is to everything ahead')?>"></label>
<label class="studio-field studio-full">Final message<textarea name="experience[finalMessage]" maxlength="600"><?=studio_h($x['finalMessage']??'May the next chapter bring kind people, beautiful surprises and moments worth remembering.')?></textarea></label>
<label class="studio-field studio-full">Custom music URL (HTTPS)<input type="url" name="experience[musicUrl]" maxlength="500" value="<?=studio_h($x['musicUrl']??'')?>" placeholder="Leave empty to use the included birthday song"></label>
</div></section></form>
<section class="studio-panel" id="memories"><h2>Gather your memories</h2><p>Add photos and short videos. These appear in the birthday page's featured memory carousel. Up to 30 items, 25 MB each; JPG, PNG, WebP, GIF, MP4, WebM and MOV are supported.</p>
<form id="mediaUploadForm" method="post" action="upload-media.php" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=studio_h($csrf)?>">
<label class="studio-drop" id="mediaDrop"><span style="font-size:28px">♡</span><strong>Drop your photos and videos here</strong><small>or choose files from your device</small><input id="mediaFiles" type="file" name="media[]" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime" multiple></label>
<div class="studio-actions" style="position:static;margin-top:13px"><span class="studio-muted" id="selectedFiles">Photos/videos choose karein (25 MB per file)</span><button class="primary" id="uploadSubmit" type="submit" disabled>Upload photos / videos</button></div></form>
<div class="media-library" id="mediaLibrary">
<?php foreach($media as $item): if(!is_array($item)||empty($item['file'])) continue; $file=basename((string)$item['file']); $url='birthday-media/'.rawurlencode($file); $isVideo=($item['type']??'')==='video'; ?>
<article class="library-item" data-media-id="<?=studio_h($file)?>">
<?php if($isVideo): ?><video src="<?=studio_h($url)?>" muted playsinline preload="metadata"></video><?php else: ?><img src="<?=studio_h($url)?>" alt="<?=studio_h($item['name']??'Birthday memory')?>" loading="lazy"><?php endif; ?>
<label class="library-caption"><span class="sr-only">Memory caption</span><input class="media-caption-input" type="text" maxlength="120" value="<?=studio_h($item['caption']??$item['name']??'Birthday memory')?>" data-file="<?=studio_h($file)?>" aria-label="Caption for this memory"></label>
<div class="media-actions"><button type="button" class="move-media" data-move="up" aria-label="Move memory earlier">↑</button><button type="button" class="move-media" data-move="down" aria-label="Move memory later">↓</button><button type="button" class="delete-media" aria-label="Remove memory" data-file="<?=studio_h($file)?>">×</button></div></article>
<?php endforeach; ?>
<?php if(!$media): ?><div class="media-empty" id="mediaEmpty">Your memory collection is waiting for its first photo or video.</div><?php endif; ?>
</div></section>
</div><aside class="studio-preview-column">
<section class="studio-panel preview-panel"><div class="preview-heading"><div><h2>See the celebration</h2><p>Make changes on the left and watch the experience update here instantly.</p></div><div class="preview-modes"><button type="button" class="preview-mode active" data-preview-mode="desktop">Desktop</button><button type="button" class="preview-mode" data-preview-mode="mobile">Phone</button></div></div><div class="preview-stage" id="previewStage"><iframe id="birthdayPreviewFrame" src="preview-frame.html" sandbox="allow-scripts" title="Live birthday page preview" loading="eager"></iframe></div><p class="studio-muted" style="margin:12px 2px 0">This preview uses your unsaved text and selected theme. Saving updates the actual birthday page.</p></section>
</aside></div>
<div class="studio-actions"><a class="ghost" href="index.php">Cancel</a><button class="primary" type="submit" form="experienceForm">Save celebration</button></div>
</main>
<script>
(function(){
const form=document.getElementById('experienceForm');const val=n=>form.elements[n]?.value||'';
const previewFrame=document.getElementById('birthdayPreviewFrame');
const previewMedia=<?=json_encode($previewMedia,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)?>;
function updateLivePreview(){
 const data={name:val('person[name]'),displayName:val('person[nickname]')||val('person[name]'),senderName:val('sender[name]'),wishBoardHeading:val('experience[wishBoardHeading]'),letterHeading:val('experience[letterHeading]'),letterMessage:val('experience[letterMessage]'),cakeHeading:val('experience[cakeHeading]'),cakeMessage:val('experience[cakeMessage]'),finalHeading:val('experience[finalHeading]'),finalMessage:val('experience[finalMessage]'),modules:{letter:form.elements['modules[letter]'].checked,cake:form.elements['modules[cake]'].checked,final:form.elements['modules[final]'].checked,music:form.elements['modules[music]'].checked},title:val('experience[title]'),subtitle:val('experience[subtitle]'),formHeading:val('experience[formHeading]'),formDescription:val('experience[formDescription]'),namePlaceholder:val('experience[namePlaceholder]'),relationshipPlaceholder:val('experience[relationshipPlaceholder]'),messagePlaceholder:val('experience[messagePlaceholder]'),submitLabel:val('experience[submitLabel]'),hint:val('experience[scrollHint]'),theme:val('theme[preset]'),accent:val('theme[accent]'),media:previewMedia};
 if(previewFrame.contentWindow)previewFrame.contentWindow.postMessage({type:'birthday-studio-preview',data},'*');
}
previewFrame.addEventListener('load',updateLivePreview);
document.querySelectorAll('[data-preview-mode]').forEach(button=>button.addEventListener('click',()=>{
 document.querySelectorAll('[data-preview-mode]').forEach(item=>item.classList.toggle('active',item===button));
 document.getElementById('previewStage').classList.toggle('phone',button.dataset.previewMode==='mobile');
}));
function sync(){updateLivePreview();}
form.addEventListener('input',sync);sync();
document.getElementById('autofillStory').addEventListener('click',()=>{
 const person=val('person[nickname]')||val('person[name]')||'someone special';
 const pronoun=val('person[pronoun]')||'they';
 const forms={he:{subject:'he',object:'him',possessive:'his'},she:{subject:'she',object:'her',possessive:'her'},they:{subject:'they',object:'them',possessive:'their'}};
 const p=forms[pronoun]||forms.they;
 const fields=['experience[title]','experience[subtitle]','experience[formHeading]','experience[formDescription]'];
 if(fields.some(name=>val(name).trim())&&!confirm('Create a fresh starter and replace the current celebration wording?'))return;
 const set=(name,value)=>{form.elements[name].value=value;};
 set('experience[title]','A day made just for '+person);
 set('experience[subtitle'],'Today is all about '+person+'. May '+p.subject+' always find reasons to smile, feel supported and enjoy the little things that make life beautiful.');
 set('experience[formHeading]','Leave a little happiness');
 set('experience[formDescription'],'Share a note, a memory or a few kind words for '+person+'.');
 set('experience[wishBoardHeading]','A little love for '+person);
 set('experience[namePlaceholder'],'Your name');
 set('experience[relationshipPlaceholder'],'How do you know '+person+'? (optional)');
 set('experience[messagePlaceholder]','Write a warm wish or a favourite memory for '+person+'…');
 set('experience[submitLabel]','Send your wish');
 set('experience[scrollHint]','Take a moment to explore '+person+'’s memories and leave a wish.');
 set('experience[metaDescription]','Celebrate '+person+' with personal wishes, favourite memories and a little happiness.');
 form.dispatchEvent(new Event('input',{bubbles:true}));
});
const themeSelect=form.elements['theme[preset]'],accentInput=form.elements['theme[accent]'];
const palette={rose:'#b98990',sage:'#849d86',lavender:'#a493bd',sunset:'#cf9879',sunshine:'#ffb84d'};
function syncPalette(usePreset){if(usePreset)accentInput.value=palette[themeSelect.value]||palette.rose;updateLivePreview();}
themeSelect.addEventListener('change',()=>syncPalette(true));accentInput.addEventListener('input',()=>syncPalette(false));syncPalette(false);
const input=document.getElementById('mediaFiles'),drop=document.getElementById('mediaDrop'),selected=document.getElementById('selectedFiles');
input.addEventListener('change',()=>{const files=Array.from(input.files||[]);const tooLarge=files.filter(file=>file.size>25*1024*1024);selected.textContent=files.length?files.length+' file(s) selected'+(tooLarge.length?' · '+tooLarge.length+' file(s) 25 MB se bade hain':''):'Photos/videos choose karein (25 MB per file)';document.getElementById('uploadSubmit').disabled=!files.length||tooLarge.length>0;});
document.getElementById('mediaUploadForm').addEventListener('submit',event=>{if(!input.files.length){event.preventDefault();selected.textContent='Pehle photos ya videos choose kijiye.';return;}if(Array.from(input.files).some(file=>file.size>25*1024*1024)){event.preventDefault();selected.textContent='Har file 25 MB se chhoti honi chahiye.';return;}const button=document.getElementById('uploadSubmit');button.disabled=true;button.textContent='Uploading…';});
['dragenter','dragover'].forEach(evt=>drop.addEventListener(evt,e=>{e.preventDefault();drop.classList.add('drag')}));
['dragleave','drop'].forEach(evt=>drop.addEventListener(evt,e=>{e.preventDefault();drop.classList.remove('drag')}));
drop.addEventListener('drop',e=>{if(e.dataTransfer.files.length){input.files=e.dataTransfer.files;input.dispatchEvent(new Event('change'));}});
const library=document.getElementById('mediaLibrary');
async function persistMediaOrder(){
 const order=Array.from(library.querySelectorAll('.library-item')).map(card=>card.dataset.mediaId);
 const body=new URLSearchParams({csrf:<?=json_encode($csrf)?>,order:JSON.stringify(order)});
 const response=await fetch('reorder-media.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body});
 const result=await response.json();if(!response.ok||!result.success)throw new Error(result.error||'Could not save memory order.');const sorted=order.map(file=>previewMedia.find(item=>item.url.endsWith(file))).filter(Boolean);previewMedia.splice(0,previewMedia.length,...sorted);updateLivePreview();
}
library.addEventListener('click',async event=>{
 const button=event.target.closest('[data-move]');if(!button)return;
 const card=button.closest('.library-item');if(!card)return;
 const sibling=button.dataset.move==='up'?card.previousElementSibling:card.nextElementSibling;
 if(!sibling||!sibling.classList.contains('library-item'))return;
 if(button.dataset.move==='up')library.insertBefore(card,sibling);else library.insertBefore(sibling,card);
 try{await persistMediaOrder();}catch(error){alert(error.message);window.location.reload();}
});
document.querySelectorAll('.media-caption-input').forEach(input=>input.addEventListener('change',async()=>{
 input.disabled=true;
 try{const body=new URLSearchParams({csrf:<?=json_encode($csrf)?>,file:input.dataset.file,caption:input.value});const response=await fetch('update-media.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body});const result=await response.json();if(!response.ok||!result.success)throw new Error(result.error||'Could not save this caption.');const previewItem=previewMedia.find(item=>item.url.endsWith(input.dataset.file));if(previewItem)previewItem.caption=input.value;updateLivePreview();}
 catch(error){alert(error.message);}finally{input.disabled=false;}
}));
document.querySelectorAll('.delete-media').forEach(button=>button.addEventListener('click',async()=>{if(!confirm('Remove this memory from the birthday page?'))return;button.disabled=true;try{const body=new URLSearchParams({csrf:<?=json_encode($csrf)?>,file:button.dataset.file});const response=await fetch('delete-media.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body});const result=await response.json();if(!response.ok||!result.success)throw new Error(result.error||'Could not remove this file.');button.closest('.library-item').remove();const removedUrl='birthday-media/'+button.dataset.file;const mediaIndex=previewMedia.findIndex(item=>item.url===removedUrl);if(mediaIndex!==-1)previewMedia.splice(mediaIndex,1);updateLivePreview();if(!document.querySelector('.library-item')){const empty=document.createElement('div');empty.className='media-empty';empty.textContent='Your memory collection is waiting for its first photo or video.';document.getElementById('mediaLibrary').appendChild(empty);}}catch(error){alert(error.message);button.disabled=false;}}));
})();
</script></body></html>