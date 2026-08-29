<?php
declare(strict_types=1);session_start();if(($_SESSION['user']??'')!=='admin'){http_response_code(403);exit('Administrator access required.');}header('Location: index.php');exit;
