<?php
require_once 'auth.php';
requireAdmin();
const COMMENTS_FILE = __DIR__.'/data/comments.json';
$time=$_GET['time']??''; $id=(int)($_GET['id']??0);
if($time===''||$id<=0) die('Thiếu tham số.');
if(!file_exists(COMMENTS_FILE)){header('Location: product_detail.php?id='.$id);exit;}
$comments=json_decode(file_get_contents(COMMENTS_FILE),true); if(!is_array($comments))$comments=[];
$new=[]; foreach($comments as $c){ if($c['time']!==$time)$new[]=$c; }
file_put_contents(COMMENTS_FILE,json_encode($new,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
header('Location: product_detail.php?id='.$id); exit;
