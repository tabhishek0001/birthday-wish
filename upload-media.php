<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
if(($_SESSION['user_type']??'')!=='developer'){http_response_code(403);exit('Access denied.');}
$csrf=(string)($_POST['csrf']??'');
if(empty($_SESSION['birthday_studio_csrf'])||!hash_equals($_SESSION['birthday_studio_csrf'],$csrf)){http_response_code(419);exit('Session expired. Reload the studio.');}
$dir=__DIR__.'/birthday-media';if(!is_dir($dir)&&!mkdir($dir,0755,true)){http_response_code(500);exit('Could not create media folder.');}
$guard="Options -Indexes\n<FilesMatch '(?i)^(\\.|.*\\.(php[0-9]?|phtml|phar|cgi|pl|py|sh|shtml))$'>\nRequire all denied\n</FilesMatch>\n";
if(!is_file($dir.'/.htaccess'))file_put_contents($dir.'/.htaccess',$guard,LOCK_EX);
$index=$dir.'/.media-index.json';$items=is_file($index)?json_decode((string)file_get_contents($index),true):[];if(!is_array($items))$items=[];
if(count($items)>=30){http_response_code(422);exit('The memory collection can contain up to 30 items.');}
if(!isset($_FILES['media'])||!is_array($_FILES['media']['name'])){http_response_code(400);exit('Choose at least one photo or video.');}
$finfo=new finfo(FILEINFO_MIME_TYPE);$allowed=['image/jpeg'=>['jpg','image'],'image/png'=>['png','image'],'image/webp'=>['webp','image'],'image/gif'=>['gif','image'],'video/mp4'=>['mp4','video'],'video/webm'=>['webm','video'],'video/quicktime'=>['mov','video']];
$added=[];$errors=[];$total=count($_FILES['media']['name']);
for($i=0;$i<$total;$i++){
 if(count($items)+count($added)>=30){$errors[]='The 30-item limit has been reached.';break;}
 $error=$_FILES['media']['error'][$i]??UPLOAD_ERR_NO_FILE;if($error===UPLOAD_ERR_NO_FILE)continue;
 if($error!==UPLOAD_ERR_OK){$errors[]='One file could not be uploaded (upload error '.$error.').';continue;}
 $tmp=$_FILES['media']['tmp_name'][$i]??'';$size=(int)($_FILES['media']['size'][$i]??0);
 if($size<1||$size>25*1024*1024){$errors[]='Each file must be smaller than 25 MB.';continue;}
 $mime=$finfo->file($tmp);if(!isset($allowed[$mime])){$errors[]='Unsupported file type: '.basename((string)$_FILES['media']['name'][$i]);continue;}
 [$ext,$kind]=$allowed[$mime];$id=bin2hex(random_bytes(16)).'.'.$ext;
 if(!move_uploaded_file($tmp,$dir.'/'.$id)){$errors[]='Could not store '.basename((string)$_FILES['media']['name'][$i]);continue;}
 $original=preg_replace('/[^\pL\pN ._()-]/u','',(string)$_FILES['media']['name'][$i]);$original=mb_substr(trim($original),0,100,'UTF-8');
 $added[]=['file'=>$id,'name'=>$original?:'Birthday memory','caption'=>$original?:'Birthday memory','type'=>$kind,'mime'=>$mime,'size'=>$size,'uploaded_at'=>gmdate('c')];
}
if($added){$items=array_values(array_merge($items,$added));$json=json_encode($items,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);$tmpIndex=$index.'.'.bin2hex(random_bytes(5)).'.tmp';if($json===false||file_put_contents($tmpIndex,$json,LOCK_EX)===false||!rename($tmpIndex,$index)){foreach($added as $item)@unlink($dir.'/'.$item['file']);@unlink($tmpIndex);http_response_code(500);exit('Could not update the media library.');}}
if($errors){$_SESSION['birthday_media_notice']=implode(' ',$errors);}
$returnTo=(string)($_POST['return_to']??'');
if($returnTo==='admin'){
 $_SESSION['birthday_admin_notice']=trim(implode(' ',array_filter(['Uploaded '.count($added).' memory'.(count($added)===1?'':'ies').'.',implode(' ',$errors)])));
 header('Location: admin/index.php#memories',true,303);exit;
}
header('Location: studio.php?uploaded='.count($added).'#memories',true,303);exit;
