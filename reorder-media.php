<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/bootstrap.php";
header('Content-Type: application/json; charset=utf-8');
if (($_SESSION['user_type'] ?? '') !== 'developer') {
    http_response_code(403); echo json_encode(['success'=>false,'error'=>'Access denied.']); exit;
}
$csrf=(string)($_POST['csrf']??'');
if(empty($_SESSION['birthday_studio_csrf'])||!hash_equals($_SESSION['birthday_studio_csrf'],$csrf)){
    http_response_code(419); echo json_encode(['success'=>false,'error'=>'Session expired. Reload the admin page.']); exit;
}
$submitted=json_decode((string)($_POST['order']??''),true);
if(!is_array($submitted)||count($submitted)>30||count($submitted)!==count(array_unique($submitted))){
    http_response_code(422); echo json_encode(['success'=>false,'error'=>'Invalid memory order.']); exit;
}
$dir=__DIR__.'/birthday-media';$index=$dir.'/.media-index.json';
$items=is_file($index)?json_decode((string)file_get_contents($index),true):[];
if(!is_array($items))$items=[];
$current=[];
foreach($items as $item){if(is_array($item)&&isset($item['file'])&&is_string($item['file']))$current[]=$item['file'];}
if(count($submitted)!==count($current)||count(array_diff($submitted,$current))||count(array_diff($current,$submitted))){
    http_response_code(409); echo json_encode(['success'=>false,'error'=>'The memory collection changed. Reload the page and try again.']); exit;
}
$byFile=[];foreach($items as $item){if(is_array($item)&&isset($item['file']))$byFile[$item['file']]=$item;}
$ordered=[];
foreach($submitted as $file){
    if(!is_string($file)||!isset($byFile[$file])){http_response_code(422);echo json_encode(['success'=>false,'error'=>'Invalid memory reference.']);exit;}
    $ordered[]=$byFile[$file];
}
$json=json_encode(array_values($ordered),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
$tmp=$index.'.'.bin2hex(random_bytes(5)).'.tmp';
if($json===false||file_put_contents($tmp,$json,LOCK_EX)===false||!rename($tmp,$index)){
    @unlink($tmp);http_response_code(500);echo json_encode(['success'=>false,'error'=>'Could not save the new memory order.']);exit;
}
echo json_encode(['success'=>true]);
