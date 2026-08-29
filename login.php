<?php
declare(strict_types=1);session_start();
if(!empty($_SESSION['user'])){header('Location: index.php');exit;}
$users=['Markus'=>'Markus1291','Sandra'=>'Sandra1291','Eric'=>'Eric1291','Franzi'=>'Franzi1291','Armin'=>'Armin1291','Thorsten'=>'Thorsten1291','admin'=>'admin1291$'];
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $u=trim((string)($_POST['username']??''));$p=(string)($_POST['password']??'');
 if(isset($users[$u])&&hash_equals($users[$u],$p)){session_regenerate_id(true);$_SESSION['user']=$u;header('Location: index.php');exit;}
 $error='Invalid username or password.';
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Games – Login</title><link rel="stylesheet" href="community.css"></head><body class="login-page"><main class="login-card"><h1>Games für die Freigeister</h1><h2>Login</h2><?php if($error): ?><p class="login-error"><?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?></p><?php endif; ?><form method="post"><label>Username<input name="username" autocomplete="username" required autofocus></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><button type="submit">Log in</button></form></main></body></html>
