<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/bootstrap.php";
header('Content-Type: application/json; charset=utf-8');
if(($_SESSION['user_type']??'')!=='developer'){http_response_code(403);echo json_encode(['success'=>false,'error'=>'Access denied.']);exit;}
$csrf=(string)($_POST['csrf']??'');if(empty($_SESSION['birthday_studio_csrf'])||!hash_equals($_SESSION['birthday_studio_csrf'],$csrf)){http_response_code(419);echo json_encode(['success'=>false,'error'=>'Session expired.']);exit;}
$file=basename((string)($_POST['file']??''));if($file===''||!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp|gif|mp4|webm|mov|mp3|m4a|wav|ogg|weba|aac|flac)$/',$file)){http_response_code(400);echo json_encode(['success'=>false,'error'=>'Invalid media reference.']);exit;}
$dir=__DIR__.'/birthday-media';$index=$dir.'/.media-index.json';$items=is_file($index)?json_decode((string)file_get_contents($index),true):[];if(!is_array($items))$items=[];
$found=false;$kept=[];foreach($items as $item){if(is_array($item)&&($item['file']??'')===$file){$found=true;continue;}$kept[]=$item;}
if(!$found){http_response_code(404);echo json_encode(['success'=>false,'error'=>'This memory was not found.']);exit;}
$tmp=$index.'.'.bin2hex(random_bytes(5)).'.tmp';$json=json_encode(array_values($kept),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
if($json===false||file_put_contents($tmp,$json,LOCK_EX)===false||!rename($tmp,$index)){@unlink($tmp);http_response_code(500);echo json_encode(['success'=>false,'error'=>'Could not update the memory collection.']);exit;}
@unlink($dir.'/'.$file);echo json_encode(['success'=>true]);
