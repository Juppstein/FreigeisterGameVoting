<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (empty($_SESSION['user'])) { http_response_code(401); echo json_encode(['error'=>'Please log in first.']); exit; }

$dataDir=__DIR__.'/community-data'; $dataFile=$dataDir.'/community.json';
function fail_json(string $message,int $status=500):never{http_response_code($status);echo json_encode(['error'=>$message],JSON_UNESCAPED_UNICODE);exit;}
if(!is_dir($dataDir)&&!mkdir($dataDir,0755,true))fail_json('Could not create community-data directory.');
if(!is_writable($dataDir))fail_json('Community data directory is not writable by PHP.');
function load_data(string $file):array{if(!file_exists($file))return ['votes'=>[],'comments'=>[]];$raw=@file_get_contents($file);if($raw===false||$raw==='')return ['votes'=>[],'comments'=>[]];$d=json_decode($raw,true);if(!is_array($d))$d=['votes'=>[],'comments'=>[]];$d['votes']??=[];$d['comments']??=[];foreach($d['comments'] as $g=>&$list){if(!is_array($list)){$list=[];continue;}foreach($list as $i=>&$c){if(!is_array($c))$c=[];if(empty($c['id']))$c['id']=hash('sha256',$g.'|'.$i.'|'.($c['created_at']??'').'|'.($c['comment']??''));}}unset($list,$c);return $d;}
function save_data(string $file,array $data):void{$tmp=$file.'.tmp-'.bin2hex(random_bytes(5));$raw=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);if(@file_put_contents($tmp,$raw,LOCK_EX)===false){@unlink($tmp);fail_json('Could not save community data.');}if(!@rename($tmp,$file)){@unlink($tmp);fail_json('Could not save community data.');}}
function input():array{$d=json_decode(file_get_contents('php://input')?:'',true);return is_array($d)?$d:[];}
function game($v):string{$v=trim((string)$v);if($v===''||strlen($v)>160)fail_json('Invalid game name.',400);return $v;}
function voter():string{return (string)($_SESSION['user']??'');}
function admin():bool{return ($_SESSION['user']??'')==='admin';}
function game_data(array $d,string $g):array{$ratings=$d['votes'][$g]??[];$sum=0;$count=0;$mine=0;$vid=voter();$details=[];foreach($ratings as $id=>$r){$r=(int)$r;if($r>=1&&$r<=5){$sum+=$r;$count++;if($id===$vid)$mine=$r;if(admin())$details[]=['id'=>$id,'rating'=>$r];}}$comments=$d['comments'][$g]??[];foreach($comments as $i=>&$c){if(empty($c['id']))$c['id']=hash('sha256',$g.'|'.$i.'|'.($c['created_at']??'').'|'.($c['comment']??''));}unset($c);$comments=array_slice(array_reverse($comments),0,50);$isAdmin=admin();$out=['average'=>$count?$sum/$count:0,'votes'=>$count,'mine'=>$mine,'comments'=>$comments,'admin'=>$isAdmin];if($isAdmin)$out['vote_details']=$details;return $out;}
try{
 $action=$_GET['action']??'';$data=load_data($dataFile);
 if($action==='all'){$games=array_unique(array_merge(array_keys($data['votes']??[]),array_keys($data['comments']??[])));$result=[];foreach($games as $g)$result[$g]=game_data($data,$g);$result['__user']=$_SESSION['user'];$result['__admin']=admin();echo json_encode($result,JSON_UNESCAPED_UNICODE);exit;}
 $in=input();$g=game($in['game']??'');
 if($action==='game'){echo json_encode(game_data($data,$g),JSON_UNESCAPED_UNICODE);exit;}
 if($_SERVER['REQUEST_METHOD']!=='POST')fail_json('POST required.',405);
 if($action==='vote'){$r=(int)($in['rating']??0);if($r<1||$r>5)fail_json('Rating must be between 1 and 5.',400);$data['votes'][$g]??=[];$data['votes'][$g][voter()]=$r;save_data($dataFile,$data);echo json_encode(game_data($data,$g),JSON_UNESCAPED_UNICODE);exit;}
 if($action==='comment'){$comment=trim((string)($in['comment']??''));if($comment===''||strlen($comment)>2000)fail_json('Comment must be 1–2000 characters.',400);$name=(string)($_SESSION['user']??'');$data['comments'][$g]??=[];$data['comments'][$g][]= ['id'=>bin2hex(random_bytes(12)),'name'=>$name,'comment'=>$comment,'created_at'=>gmdate('c'),'user'=>$_SESSION['user']];save_data($dataFile,$data);echo json_encode(game_data($data,$g),JSON_UNESCAPED_UNICODE);exit;}
 if($action==='delete_comment'){if(!admin())fail_json('Administrator access required.',403);$id=(string)($in['id']??'');$list=$data['comments'][$g]??[];$new=[];$found=false;foreach($list as $c){$cid=$c['id']??'';if($cid===$id){$found=true;continue;}$new[]=$c;}if(!$found)fail_json('Comment not found.',404);$data['comments'][$g]=$new;save_data($dataFile,$data);echo json_encode(game_data($data,$g),JSON_UNESCAPED_UNICODE);exit;}
 if($action==='delete_vote'){if(!admin())fail_json('Administrator access required.',403);$id=(string)($in['id']??'');if(isset($data['votes'][$g][$id])){unset($data['votes'][$g][$id]);save_data($dataFile,$data);echo json_encode(game_data($data,$g),JSON_UNESCAPED_UNICODE);exit;}fail_json('Vote not found.',404);}
 fail_json('Unknown action.',404);
}catch(Throwable $e){fail_json('Server error: '.$e->getMessage(),500);}
