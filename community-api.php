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
function load_data(string $file):array{ if(!file_exists($file)) return ['games'=>[],'votes'=>[],'comments'=>[],'users'=>[]]; $raw=@file_get_contents($file); if($raw===false||$raw==='') return ['games'=>[],'votes'=>[],'comments'=>[],'users'=>[]]; $d=@json_decode($raw,true); if(!is_array($d)) return ['games'=>[],'votes'=>[],'comments'=>[],'users'=>[]]; if(!isset($d['games'])||!is_array($d['games'])) $d['games']=[]; if(!isset($d['votes'])||!is_array($d['votes'])) $d['votes']=[]; if(!isset($d['comments'])||!is_array($d['comments'])) $d['comments']=[]; if(!isset($d['users'])||!is_array($d['users'])) $d['users']=[]; return $d; }
function save_data(string $file,array $data):void{ $tmp=$file.'.tmp-'.bin2hex(random_bytes(5)); $raw=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); if(@file_put_contents($tmp,$raw,LOCK_EX)===false) fail_json('Failed to write data file.',500); if(!@rename($tmp,$file)) { @unlink($tmp); fail_json('Failed to replace data file.',500); } }
function input():array{ $d=json_decode(file_get_contents('php://input')?:'',true); return is_array($d)?$d:[]; }
function game_param($v):string{ $v=trim((string)$v); if($v===''||strlen($v)>160) fail_json('Invalid game name.',400); return $v; }
function voter():string{return (string)($_SESSION['user']??'');}
function admin():bool{return ($_SESSION['user']??'')==='admin';}
function slugify(string $s):string{
    // mbstring may not be available; fall back to strtolower
    $s = (function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s));
    // replace any non-alphanumeric with hyphen
    $s = preg_replace('/[^a-z0-9]+/u', '-', $s);
    $s = trim($s, '-');
    if($s === '') $s = bin2hex(random_bytes(4));
    return substr($s, 0, 60);
}

function game_data(array $d,string $g):array{
    $ratings=$d['votes'][$g]??[];
    $sum=0; $count=0; $mine=null; $vid=voter(); $details=[];
    foreach($ratings as $id=>$r){ $r=(int)$r; if($r>=1 && $r<=5){ $sum+=$r; $count++; $details[]=['id'=>$id,'rating'=>$r]; if($id===$vid) $mine=$r; } }
    $avg = $count?($sum/$count):0;
    $comments=[];
    foreach($d['comments'][$g]??[] as $c){ $author=(string)($c['author']??''); $rating = $d['votes'][$g][$author]??null; $comments[]=['id'=>$c['id']??'','author'=>$author,'text'=>$c['text']??'','created_at'=>$c['created_at']??'','edited_at'=>$c['edited_at']??null,'rating'=>$rating]; }
    return ['average'=>round($avg,2),'count'=>$count,'mine'=>$mine,'vote_details'=>$details,'comments'=>$comments,'admin'=>admin()];
}

try{
    $action=$_GET['action']??''; $data=load_data($dataFile);

    // public read endpoints
    if($action==='all'){
        $games=array_unique(array_merge(array_keys($data['votes']??[]),array_keys($data['comments']??[])));
        $result=[]; foreach($games as $g) $result[$g]=game_data($data,$g);
        $result['__user']=(string)($_SESSION['user']??''); echo json_encode($result,JSON_UNESCAPED_UNICODE); exit;
    }
    if($action==='game'){
        $in=input(); $g=game_param($in['game']??''); echo json_encode(game_data($data,$g),JSON_UNESCAPED_UNICODE); exit;
    }
    if($action==='games'){
        // return games list (for admin and client); keep order
        $games=array_values($data['games']??[]);
        echo json_encode(['games'=>$games,'admin'=>admin()],JSON_UNESCAPED_UNICODE); exit;
    }

    if($action==='users_list'){
        // return list of usernames (admin only info also returned)
        $users = array_keys($data['users']??[]);
        echo json_encode(['users'=>$users,'admin'=>admin()],JSON_UNESCAPED_UNICODE); exit;
    }

    if($_SERVER['REQUEST_METHOD']!=='POST') fail_json('POST required.',405);

    if($action==='vote'){
        $in=input(); $g=game_param($in['game']??''); $r=(int)($in['rating']??0); if($r<1||$r>5) fail_json('Rating must be between 1 and 5.',400);
        if(!isset($data['votes'][$g])) $data['votes'][$g]=[]; $data['votes'][$g][voter()]=$r; save_data($dataFile,$data); echo json_encode(['ok'=>true]); exit;
    }

    if($action==='comment'){
        $in=input(); $g=game_param($in['game']??''); $comment=trim((string)($in['comment']??'')); if($comment===''||strlen($comment)>2000) fail_json('Comment must be 1–2000 characters.',400);
        $name=voter(); if($name==='') fail_json('Invalid user.',400);
        $id=bin2hex(random_bytes(6)); $entry=['id'=>$id,'author'=>$name,'text'=>$comment,'created_at'=>date('c')]; if(!isset($data['comments'][$g])) $data['comments'][$g]=[]; $data['comments'][$g][]=$entry; save_data($dataFile,$data); echo json_encode(['ok'=>true,'comment'=>$entry],JSON_UNESCAPED_UNICODE); exit;
    }

    if($action==='edit_comment'){
        $in=input(); $g=game_param($in['game']??''); $id=(string)($in['id']??''); $comment=trim((string)($in['comment']??'')); if($id==='') fail_json('Comment id required.',400);
        if($comment===''||strlen($comment)>2000) fail_json('Comment must be 1–2000 characters.',400);
        $list=$data['comments'][$g]??[]; $found=false; foreach($list as $k=>$c){ if(($c['id']??'')===$id){ $found=true; $author=$c['author']??''; if(!admin() && $author!==voter()) fail_json('Permission denied.',403); $data['comments'][$g][$k]['text']=$comment; $data['comments'][$g][$k]['edited_at']=date('c'); } }
        if(!$found) fail_json('Comment not found.',404); save_data($dataFile,$data); echo json_encode(['ok'=>true]); exit;
    }

    if($action==='delete_comment'){
        $in=input(); $g=game_param($in['game']??''); $id=(string)($in['id']??''); if($id==='') fail_json('Comment id required.',400);
        $list=$data['comments'][$g]??[]; $new=[]; $found=false; foreach($list as $c){ if(($c['id']??'')===$id){ $found=true; $author=$c['author']??''; if(!admin() && $author!==voter()) fail_json('Permission denied.',403); } else $new[]=$c; }
        if(!$found) fail_json('Comment not found.',404); $data['comments'][$g]=$new; save_data($dataFile,$data); echo json_encode(['ok'=>true]); exit;
    }

    if($action==='delete_vote'){
        if(!admin()) fail_json('Administrator access required.',403);
        $in=input(); $g=game_param($in['game']??''); $id=(string)($in['id']??''); if($id==='') fail_json('Vote id required.',400);
        if(isset($data['votes'][$g][$id])){ unset($data['votes'][$g][$id]); save_data($dataFile,$data); echo json_encode(['ok'=>true]); exit; }
        fail_json('Vote not found.',404);
    }

    // Admin: manage games
    if($action==='games_add'){
        if(!admin()) fail_json('Administrator access required.',403);
        $in=input(); $name=trim((string)($in['name']??'')); if($name==='') fail_json('Game name required.',400);
        $players=trim((string)($in['players']??'')); $genre=trim((string)($in['genre']??'')); $steam=trim((string)($in['steam']??'')); $notes=trim((string)($in['notes']??'')); $listNum=(int)($in['list']??1);
        $id=slugify($name);
        // ensure unique id
        $base=$id; $i=1; while(isset($data['games'][$id])){ $id = $base.'-'.($i++); }
        $obj=['id'=>$id,'name'=>$name,'players'=>$players,'genre'=>$genre,'steam'=>$steam,'notes'=>$notes,'list'=> $listNum];
        $data['games'][$id]=$obj; save_data($dataFile,$data); echo json_encode(['ok'=>true,'game'=>$obj],JSON_UNESCAPED_UNICODE); exit;
    }

    if($action==='games_edit'){
        if(!admin()) fail_json('Administrator access required.',403);
        $in=input(); $id=(string)($in['id']??''); if($id==='') fail_json('Game id required.',400);
        if(!isset($data['games'][$id])) fail_json('Game not found.',404);
        $g=$data['games'][$id]; $fields=['name','players','genre','steam','notes','list']; foreach($fields as $f){ if(array_key_exists($f,$in)) $g[$f]=is_string($in[$f])?trim((string)$in[$f]):$in[$f]; }
        $data['games'][$id]=$g; save_data($dataFile,$data); echo json_encode(['ok'=>true,'game'=>$g],JSON_UNESCAPED_UNICODE); exit;
    }

    if($action==='games_delete'){
        if(!admin()) fail_json('Administrator access required.',403);
        $in=input(); $id=(string)($in['id']??''); if($id==='') fail_json('Game id required.',400);
        if(!isset($data['games'][$id])) fail_json('Game not found.',404);
        unset($data['games'][$id]); save_data($dataFile,$data); echo json_encode(['ok'=>true]); exit;
    }

    // Admin: manage users
    if($action==='users_add'){
        if(!admin()) fail_json('Administrator access required.',403);
        $in=input(); $username=trim((string)($in['username']??'')); $password=(string)($in['password']??'');
        if($username==='') fail_json('Username required.',400);
        if(!preg_match('/^[A-Za-z0-9_\-]{2,40}$/',$username)) fail_json('Username may only contain letters, numbers, dash or underscore (2-40 chars).',400);
        if($password==='') fail_json('Password required.',400);
        if(isset($data['users'][$username])) fail_json('User exists.',409);
        $data['users'][$username]=password_hash($password,PASSWORD_DEFAULT);
        save_data($dataFile,$data);
        echo json_encode(['ok'=>true,'user'=>$username],JSON_UNESCAPED_UNICODE); exit;
    }

    if($action==='users_edit'){
        if(!admin()) fail_json('Administrator access required.',403);
        $in=input(); $old=(string)($in['old']??''); $new=(string)($in['new']??''); $password=(array_key_exists('password',$in)?(string)$in['password']:null);
        if($old==='') fail_json('Old username required.',400);
        if(!isset($data['users'][$old])) fail_json('User not found.',404);
        $target=$old;
        if($new!==''){
            if(!preg_match('/^[A-Za-z0-9_\-]{2,40}$/',$new)) fail_json('Username may only contain letters, numbers, dash or underscore (2-40 chars).',400);
            if($new!==$old && isset($data['users'][$new])) fail_json('Target username already exists.',409);
            // rename votes keys
            foreach($data['votes'] as $gk=>$votesArr){ if(isset($votesArr[$old])){ $data['votes'][$gk][$new]=$votesArr[$old]; unset($data['votes'][$gk][$old]); } }
            // rename comment authors
            foreach($data['comments'] as $gk=>$commentsArr){ foreach($commentsArr as $i=>$c){ if(($c['author']??'')===$old){ $data['comments'][$gk][$i]['author']=$new; } } }
            // replace user key
            $data['users'][$new] = $data['users'][$old]; unset($data['users'][$old]);
            $target = $new;
        }
        if($password!==null && $password!==''){
            $data['users'][$target]=password_hash($password,PASSWORD_DEFAULT);
        }
        save_data($dataFile,$data);
        echo json_encode(['ok'=>true,'user'=>$target],JSON_UNESCAPED_UNICODE); exit;
    }

    if($action==='users_delete'){
        if(!admin()) fail_json('Administrator access required.',403);
        $in=input(); $username=(string)($in['username']??''); if($username==='') fail_json('Username required.',400);
        if(!isset($data['users'][$username])) fail_json('User not found.',404);
        // remove votes
        foreach($data['votes'] as $gk=>$votesArr){ if(isset($votesArr[$username])) unset($data['votes'][$gk][$username]); }
        // remove comments
        foreach($data['comments'] as $gk=>$commentsArr){ $new=[]; foreach($commentsArr as $c){ if(($c['author']??'')===$username) continue; $new[]=$c; } $data['comments'][$gk]=$new; }
        unset($data['users'][$username]); save_data($dataFile,$data);
        echo json_encode(['ok'=>true],JSON_UNESCAPED_UNICODE); exit;
    }

    fail_json('Unknown action.',404);
}catch(Throwable $e){ fail_json('Server error: '.$e->getMessage(),500); }
