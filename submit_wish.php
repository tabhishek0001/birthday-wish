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
if($name===''||$message===''){http_response_code(422);echo json_encode(['success'=>false,'error'=>'Please add your name and a message.']);exit;}
if(mb_strlen($message,'UTF-8')<2){http_response_code(422);echo json_encode(['success'=>false,'error'=>'Please write a little more in your wish.']);exit;}
$now=time();$last=(int)($_SESSION['birthday_last_wish_at']??0);
if($last>0&&($now-$last)<15){http_response_code(429);echo json_encode(['success'=>false,'error'=>'Please wait a few seconds before sending another wish.']);exit;}
$wish=['name'=>$name,'message'=>$message,'pahchan'=>$pahchan,'timestamp'=>$now];
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
