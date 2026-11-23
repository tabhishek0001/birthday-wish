<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/bootstrap.php";
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Kolkata');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'error'=>'Method not allowed.']); exit; }
$clean = static function($value, int $limit): string {
    if (!is_string($value)) return '';
    return mb_substr(trim(str_replace("\0", '', $value)), 0, $limit, 'UTF-8');
};
$name=$clean($_POST['name']??'',80); $message=$clean($_POST['message']??'',1200); $pahchan=$clean($_POST['pahchan']??'',100);
if($name===''||mb_strtolower($name,'UTF-8')==='aapka naam'||$message===''){http_response_code(422);echo json_encode(['success'=>false,'error'=>'Please replace the prefilled name with your own name and add a wish.']);exit;}
if(mb_strlen($message,'UTF-8')<2){http_response_code(422);echo json_encode(['success'=>false,'error'=>'Please write a little more in your wish.']);exit;}
$now=time();$last=(int)($_SESSION['birthday_last_wish_at']??0);
if($last>0&&($now-$last)<15){http_response_code(429);echo json_encode(['success'=>false,'error'=>'Please wait a few seconds before sending another wish.']);exit;}
$uploadedMedia=[];$savedMediaFiles=[];
if(isset($_FILES['attachments'])&&is_array($_FILES['attachments']['name'])){
 $names=$_FILES['attachments']['name'];$fileCount=count(array_filter($names,static fn($v)=>is_string($v)&&$v!==''));
 if($fileCount>3){http_response_code(422);echo json_encode(['success'=>false,'error'=>'Maximum 3 attachments are allowed.']);exit;}
 $mediaDir=__DIR__.'/wish-media';if(!is_dir($mediaDir)&&!mkdir($mediaDir,0755,true)){http_response_code(500);echo json_encode(['success'=>false,'error'=>'Media upload folder is unavailable.']);exit;}
 $guard="Options -Indexes\n<FilesMatch '(?i)^(\\.|.*\\.(php[0-9]?|phtml|phar|cgi|pl|py|sh|shtml))
$file=__DIR__.'/wishes.json';$handle=@fopen($file,'c+');
if(!$handle||!flock($handle,LOCK_EX)){if(is_resource($handle))fclose($handle);http_response_code(500);echo json_encode(['success'=>false,'error'=>'Your wish could not be saved. Please try again.']);exit;}
$contents=stream_get_contents($handle);$wishes=$contents!==false&&trim($contents)!==''?json_decode($contents,true):[];if(!is_array($wishes))$wishes=[];$wishes[]=$wish;if(count($wishes)>5000)$wishes=array_slice($wishes,-5000);
$encoded=json_encode($wishes,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);$written=$encoded!==false;
if($written){rewind($handle);$written=ftruncate($handle,0)&&fwrite($handle,$encoded)!==false&&fflush($handle);}flock($handle,LOCK_UN);fclose($handle);
if(!$written){foreach($savedMediaFiles as $savedFile)@unlink(__DIR__.'/wish-media/'.$savedFile);http_response_code(500);echo json_encode(['success'=>false,'error'=>'Your wish could not be saved. Please try again.']);exit;}
$_SESSION['birthday_last_wish_at']=$now;

$configPath=__DIR__.'/experience.json';$config=is_file($configPath)?json_decode((string)@file_get_contents($configPath),true):[];if(!is_array($config))$config=[];
$notifications=is_array($config['notifications']??null)?$config['notifications']:[];
$to=trim((string)($notifications['to']??''));
$fromEmail=trim((string)($notifications['fromEmail']??''));
$fromName=trim((string)($notifications['fromName']??'Birthday Bot'))?:'Birthday Bot';
if($to!==''&&filter_var($to,FILTER_VALIDATE_EMAIL)){
  $safeName=htmlspecialchars($name,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$safeMessage=nl2br(htmlspecialchars($message,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'));$safePahchan=$pahchan!==''?htmlspecialchars($pahchan,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'):'🤫 Not shared';
  $subjectName=preg_replace('/[\r\n]+/',' ',$name);$subject="🎉 New Birthday Wish from {$subjectName}!";
  $templatePath=__DIR__.'/email-template.html';$template=is_file($templatePath)?file_get_contents($templatePath):false;
  if($template!==false){
    $shareUrl=trim((string)($config['experience']['shareUrl']??''));if($shareUrl==='')$shareUrl='https://www.docsansar.com/birthday';
    $template=str_replace(['{{NAME}}','{{MESSAGE}}','{{TIMESTAMP}}','{{PAHCHAN}}','{{SHARE_URL}}'],[$safeName,$safeMessage,date('d M Y, h:i A',$now),$safePahchan,htmlspecialchars($shareUrl,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')],$template);
    $headers="MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
    $headers.="From: ".preg_replace('/[\r\n]+/',' ',$fromName)." <".($fromEmail!==''?$fromEmail:$to).">\r\n";
    foreach(['cc','bcc'] as $type){
      $list=trim((string)($notifications[$type]??''));
      if($list!==''){
        $valid=array_filter(array_map('trim',preg_split('/[,;]+/',$list)),static fn($email)=>filter_var($email,FILTER_VALIDATE_EMAIL));
        if($valid)$headers.=strtoupper($type).": ".implode(', ',$valid)."\r\n";
      }
    }
    @mail($to,$subject,$template,$headers);
  }
}
echo json_encode(['success'=>true]);
>\nRequire all denied\n</FilesMatch>\n";if(!is_file($mediaDir.'/.htaccess'))file_put_contents($mediaDir.'/.htaccess',$guard,LOCK_EX);
 $allowed=['image/jpeg'=>['jpg','image'],'image/png'=>['png','image'],'image/webp'=>['webp','image'],'image/gif'=>['gif','image'],'video/mp4'=>['mp4','video'],'video/webm'=>['webm','video'],'video/quicktime'=>['mov','video'],'audio/mpeg'=>['mp3','audio'],'audio/mp4'=>['m4a','audio'],'audio/x-m4a'=>['m4a','audio'],'audio/wav'=>['wav','audio'],'audio/x-wav'=>['wav','audio'],'audio/ogg'=>['ogg','audio'],'audio/webm'=>['weba','audio'],'audio/aac'=>['aac','audio'],'application/pdf'=>['pdf','document']];
 $finfo=new finfo(FILEINFO_MIME_TYPE);
 for($i=0;$i<count($names);$i++){
  $error=$_FILES['attachments']['error'][$i]??UPLOAD_ERR_NO_FILE;if($error===UPLOAD_ERR_NO_FILE)continue;
  if($error!==UPLOAD_ERR_OK){foreach($savedMediaFiles as $savedFile)@unlink($mediaDir.'/'.$savedFile);http_response_code(422);echo json_encode(['success'=>false,'error'=>'An attachment could not be uploaded. Please retry with smaller files.']);exit;}
  $tmp=$_FILES['attachments']['tmp_name'][$i]??'';$size=(int)($_FILES['attachments']['size'][$i]??0);
  if($size<1||$size>10*1024*1024){foreach($savedMediaFiles as $savedFile)@unlink($mediaDir.'/'.$savedFile);http_response_code(422);echo json_encode(['success'=>false,'error'=>'Each attachment must be 10 MB or smaller.']);exit;}
  $mime=$finfo->file($tmp);if(!isset($allowed[$mime])){foreach($savedMediaFiles as $savedFile)@unlink($mediaDir.'/'.$savedFile);http_response_code(422);echo json_encode(['success'=>false,'error'=>'Unsupported attachment type. Use a photo, video, audio file or PDF.']);exit;}
  [$ext,$kind]=$allowed[$mime];$file=bin2hex(random_bytes(16)).'.'.$ext;
  if(!move_uploaded_file($tmp,$mediaDir.'/'.$file)){foreach($savedMediaFiles as $savedFile)@unlink($mediaDir.'/'.$savedFile);http_response_code(500);echo json_encode(['success'=>false,'error'=>'Could not save an attachment. Please retry.']);exit;}
  $savedMediaFiles[]=$file;$uploadedMedia[]=['file'=>$file,'type'=>$kind,'mime'=>$mime,'size'=>$size];
 }
}
$wish=['name'=>$name,'message'=>$message,'pahchan'=>$pahchan,'timestamp'=>$now];if($uploadedMedia)$wish['media']=$uploadedMedia;
$file=__DIR__.'/wishes.json';$handle=@fopen($file,'c+');
if(!$handle||!flock($handle,LOCK_EX)){if(is_resource($handle))fclose($handle);http_response_code(500);echo json_encode(['success'=>false,'error'=>'Your wish could not be saved. Please try again.']);exit;}
$contents=stream_get_contents($handle);$wishes=$contents!==false&&trim($contents)!==''?json_decode($contents,true):[];if(!is_array($wishes))$wishes=[];$wishes[]=$wish;if(count($wishes)>5000)$wishes=array_slice($wishes,-5000);
$encoded=json_encode($wishes,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);$written=$encoded!==false;
if($written){rewind($handle);$written=ftruncate($handle,0)&&fwrite($handle,$encoded)!==false&&fflush($handle);}flock($handle,LOCK_UN);fclose($handle);
if(!$written){http_response_code(500);echo json_encode(['success'=>false,'error'=>'Your wish could not be saved. Please try again.']);exit;}
$_SESSION['birthday_last_wish_at']=$now;

$configPath=__DIR__.'/experience.json';$config=is_file($configPath)?json_decode((string)@file_get_contents($configPath),true):[];if(!is_array($config))$config=[];
$notifications=is_array($config['notifications']??null)?$config['notifications']:[];
$to=trim((string)($notifications['to']??''));
$fromEmail=trim((string)($notifications['fromEmail']??''));
$fromName=trim((string)($notifications['fromName']??'Birthday Bot'))?:'Birthday Bot';
if($to!==''&&filter_var($to,FILTER_VALIDATE_EMAIL)){
  $safeName=htmlspecialchars($name,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$safeMessage=nl2br(htmlspecialchars($message,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'));$safePahchan=$pahchan!==''?htmlspecialchars($pahchan,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'):'🤫 Not shared';
  $subjectName=preg_replace('/[\r\n]+/',' ',$name);$subject="🎉 New Birthday Wish from {$subjectName}!";
  $templatePath=__DIR__.'/email-template.html';$template=is_file($templatePath)?file_get_contents($templatePath):false;
  if($template!==false){
    $shareUrl=trim((string)($config['experience']['shareUrl']??''));if($shareUrl==='')$shareUrl='https://www.docsansar.com/birthday';
    $template=str_replace(['{{NAME}}','{{MESSAGE}}','{{TIMESTAMP}}','{{PAHCHAN}}','{{SHARE_URL}}'],[$safeName,$safeMessage,date('d M Y, h:i A',$now),$safePahchan,htmlspecialchars($shareUrl,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')],$template);
    $headers="MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
    $headers.="From: ".preg_replace('/[\r\n]+/',' ',$fromName)." <".($fromEmail!==''?$fromEmail:$to).">\r\n";
    foreach(['cc','bcc'] as $type){
      $list=trim((string)($notifications[$type]??''));
      if($list!==''){
        $valid=array_filter(array_map('trim',preg_split('/[,;]+/',$list)),static fn($email)=>filter_var($email,FILTER_VALIDATE_EMAIL));
        if($valid)$headers.=strtoupper($type).": ".implode(', ',$valid)."\r\n";
      }
    }
    @mail($to,$subject,$template,$headers);
  }
}
echo json_encode(['success'=>true]);
