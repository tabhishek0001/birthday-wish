<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/bootstrap.php";
header('Content-Type: application/json; charset=utf-8');
if(($_SESSION['user_type']??'')!=='developer'){http_response_code(403);echo json_encode(['success'=>false,'error'=>'Access denied.']);exit;}
$csrf=(string)($_POST['csrf']??'');
if(empty($_SESSION['birthday_studio_csrf'])||!hash_equals($_SESSION['birthday_studio_csrf'],$csrf)){http_response_code(419);echo json_encode(['success'=>false,'error'=>'Session expired.']);exit;}
$file=basename((string)($_POST['file']??''));
if(!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp|gif|mp4|webm|mov)$/',$file)){http_response_code(400);echo json_encode(['success'=>false,'error'=>'Invalid memory reference.']);exit;}
$caption=$_POST['caption']??'';
if(!is_string($caption)){http_response_code(422);echo json_encode(['success'=>false,'error'=>'Invalid caption.']);exit;}
$caption=mb_substr(trim($caption),0,120,'UTF-8');
$dir=__DIR__.'/birthday-media';$index=$dir.'/.media-index.json';
$items=is_file($index)?json_decode((string)file_get_contents($index),true):[];
if(!is_array($items))$items=[];
$found=false;
foreach($items as &$item){if(is_array($item)&&($item['file']??'')===$file){$item['caption']=$caption;$found=true;break;}}
unset($item);
if(!$found){http_response_code(404);echo json_encode(['success'=>false,'error'=>'Memory not found.']);exit;}
$json=json_encode(array_values($items),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
$tmp=$index.'.'.bin2hex(random_bytes(5)).'.tmp';
if($json===false||file_put_contents($tmp,$json,LOCK_EX)===false||!rename($tmp,$index)){@unlink($tmp);http_response_code(500);echo json_encode(['success'=>false,'error'=>'Could not save caption.']);exit;}
echo json_encode(['success'=>true]);
