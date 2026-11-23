<?php
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
if(($_SESSION['user_type']??'')!=='developer'){http_response_code(403);echo json_encode(['success'=>false,'error'=>'Access denied.']);exit;}
$csrf=(string)($_POST['csrf']??'');
if(empty($_SESSION['birthday_studio_csrf'])||!hash_equals($_SESSION['birthday_studio_csrf'],$csrf)){http_response_code(419);echo json_encode(['success'=>false,'error'=>'Session expired.']);exit;}
$order=json_decode((string)($_POST['order']??''),true);
if(!is_array($order)||count($order)>30){http_response_code(422);echo json_encode(['success'=>false,'error'=>'Invalid memory order.']);exit;}
$dir=__DIR__.'/birthday-media';$index=$dir.'/.media-index.json';
$items=is_file($index)?json_decode((string)file_get_contents($index),true):[];
if(!is_array($items))$items=[];
$byFile=[];foreach($items as $item){if(is_array($item)&&isset($item['file']))$byFile[$item['file']]=$item;}
$cleanOrder=[];$seen=[];
foreach($order as $file){
 if(!is_string($file)||!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp|gif|mp4|webm|mov)$/',$file)||!isset($byFile[$file])||isset($seen[$file])){http_response_code(422);echo json_encode(['success'=>false,'error'=>'Memory order contains an invalid item.']);exit;}
 $seen[$file]=true;$cleanOrder[]=$byFile[$file];
}
if(count($cleanOrder)!==count($byFile)){http_response_code(422);echo json_encode(['success'=>false,'error'=>'The order must include every saved memory.']);exit;}
$json=json_encode($cleanOrder,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
$tmp=$index.'.'.bin2hex(random_bytes(5)).'.tmp';
if($json===false||file_put_contents($tmp,$json,LOCK_EX)===false||!rename($tmp,$index)){@unlink($tmp);http_response_code(500);echo json_encode(['success'=>false,'error'=>'Could not save memory order.']);exit;}
echo json_encode(['success'=>true]);
