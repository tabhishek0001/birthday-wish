<?php
require_once __DIR__ . '/../includes/bootstrap.php';
if (($_SESSION['user_type'] ?? '') !== 'developer') { http_response_code(403); exit('Forbidden'); }
$csrf = (string)($_POST['csrf'] ?? '');
if (empty($_SESSION['birthday_studio_csrf']) || !hash_equals($_SESSION['birthday_studio_csrf'], $csrf)) { http_response_code(419); exit('Session expired. Reopen Birthday Studio.'); }
$clean = static function($v, $max) { return is_string($v) ? mb_substr(trim($v), 0, $max, 'UTF-8') : ''; };
$name = $clean($_POST['person']['name'] ?? '', 80);
if ($name === '') { http_response_code(422); exit('Birthday person name is required.'); }
$pronoun = $_POST['person']['pronoun'] ?? 'they'; if (!in_array($pronoun, ['he','she','they'], true)) $pronoun = 'they';
$birthday = $clean($_POST['person']['birthday'] ?? '', 10);
if ($birthday !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday)) { http_response_code(422); exit('Invalid birthday date.'); }
$share = $clean($_POST['experience']['shareUrl'] ?? '', 250);
$musicUrl = $clean($_POST['experience']['musicUrl'] ?? '', 500);
if ($musicUrl !== '') {
    $musicParts = parse_url($musicUrl);
    if (!filter_var($musicUrl, FILTER_VALIDATE_URL) || strtolower((string)($musicParts['scheme'] ?? '')) !== 'https') {
        http_response_code(422); exit('Custom music must use a valid HTTPS URL.');
    }
}
$moduleInput = is_array($_POST['modules'] ?? null) ? $_POST['modules'] : [];
$modules = ['letter'=>isset($moduleInput['letter']),'cake'=>isset($moduleInput['cake']),'final'=>isset($moduleInput['final']),'music'=>isset($moduleInput['music'])];
if ($share !== '' && !filter_var($share, FILTER_VALIDATE_URL)) { http_response_code(422); exit('Invalid public page URL.'); }
$data = ['person'=>['name'=>$name,'nickname'=>$clean($_POST['person']['nickname']??'',80),'birthday'=>$birthday,'pronoun'=>$pronoun],
'sender'=>['name'=>$clean($_POST['sender']['name']??'',80)],
'experience'=>['title'=>$clean($_POST['experience']['title']??'',120),'subtitle'=>$clean($_POST['experience']['subtitle']??'',600),'formHeading'=>$clean($_POST['experience']['formHeading']??'',100),'formDescription'=>$clean($_POST['experience']['formDescription']??'',220),'shareUrl'=>$share,'wishBoardHeading'=>$clean($_POST['experience']['wishBoardHeading']??'',100),'namePlaceholder'=>$clean($_POST['experience']['namePlaceholder']??'',100),'relationshipPlaceholder'=>$clean($_POST['experience']['relationshipPlaceholder']??'',140),'messagePlaceholder'=>$clean($_POST['experience']['messagePlaceholder']??'',180),'submitLabel'=>$clean($_POST['experience']['submitLabel']??'',70),'scrollHint'=>$clean($_POST['experience']['scrollHint']??'',140),'metaDescription'=>$clean($_POST['experience']['metaDescription']??'',220),'letterHeading'=>$clean($_POST['experience']['letterHeading']??'',100),'letterMessage'=>$clean($_POST['experience']['letterMessage']??'',1400),'letterButton'=>$clean($_POST['experience']['letterButton']??'',80),'cakeHeading'=>$clean($_POST['experience']['cakeHeading']??'',100),'cakeMessage'=>$clean($_POST['experience']['cakeMessage']??'',500),'cakeButton'=>$clean($_POST['experience']['cakeButton']??'',80),'finalHeading'=>$clean($_POST['experience']['finalHeading']??'',100),'finalMessage'=>$clean($_POST['experience']['finalMessage']??'',600),'musicUrl'=>$musicUrl],'theme'=>['preset'=>in_array(($_POST['theme']['preset']??'rose'),['rose','sage','lavender','sunset'],true)?$_POST['theme']['preset']:'rose','accent'=>preg_match('/^#[0-9a-fA-F]{6}$/',(string)($_POST['theme']['accent']??''))?$_POST['theme']['accent']:'#b98990'],'modules'=>$modules];
$target=__DIR__.'/experience.json'; $tmp=$target.'.'.bin2hex(random_bytes(5)).'.tmp';
$json=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
if ($json===false || file_put_contents($tmp,$json,LOCK_EX)===false || !rename($tmp,$target)) { @unlink($tmp); http_response_code(500); exit('Could not save settings.'); }
header('Location: studio.php?saved=1',true,303); exit;
