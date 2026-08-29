<?php
declare(strict_types=1);session_start();

if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$dataDir = __DIR__ . '/community-data';
$dataFile = $dataDir . '/community.json';
$defaultUsers = [
    'Markus' => 'Markus1291',
    'Sandra' => 'Sandra1291',
    'Eric' => 'Eric1291',
    'Franzi' => 'Franzi1291',
    'Armin' => 'Armin1291',
    'Thorsten' => 'Thorsten1291',
    'admin' => 'admin1291$'
];

function load_data_file(string $file): array {
    if (!file_exists($file)) return ['games' => [], 'votes' => [], 'comments' => [], 'users' => []];
    $raw = @file_get_contents($file);
    if ($raw === false || $raw === '') return ['games' => [], 'votes' => [], 'comments' => [], 'users' => []];
    $d = @json_decode($raw, true);
    if (!is_array($d)) return ['games' => [], 'votes' => [], 'comments' => [], 'users' => []];
    if (!isset($d['users']) || !is_array($d['users'])) $d['users'] = [];
    if (!isset($d['votes']) || !is_array($d['votes'])) $d['votes'] = [];
    if (!isset($d['comments']) || !is_array($d['comments'])) $d['comments'] = [];
    if (!isset($d['games']) || !is_array($d['games'])) $d['games'] = [];
    return $d;
}

function save_data_file(string $file, array $data): bool {
    $tmp = $file . '.tmp-' . bin2hex(random_bytes(5));
    $raw = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (@file_put_contents($tmp, $raw, LOCK_EX) === false) return false;
    if (!@rename($tmp, $file)) { @unlink($tmp); return false; }
    return true;
}

$error = '';
$users = [];
$d = ['games' => [], 'votes' => [], 'comments' => [], 'users' => []];
if (is_dir($dataDir)) {
    $d = load_data_file($dataFile);
    $users = $d['users'] ?? [];
}

// If there are no persisted users, try to persist the default users into the data file as hashed passwords
if (empty($users)) {
    // Build hashed users from defaults
    $hashed = [];
    foreach ($defaultUsers as $u => $pw) {
        $hashed[$u] = password_hash($pw, PASSWORD_DEFAULT);
    }
    $d['users'] = $hashed;
    // ensure other keys exist
    if (!isset($d['games']) || !is_array($d['games'])) $d['games'] = [];
    if (!isset($d['votes']) || !is_array($d['votes'])) $d['votes'] = [];
    if (!isset($d['comments']) || !is_array($d['comments'])) $d['comments'] = [];
    // attempt to save; if it fails, fall back to in-memory defaults so login still works
    if (!save_data_file($dataFile, $d)) {
        // fallback to plaintext defaults in-memory (migration on login is supported)
        $users = $defaultUsers;
    } else {
        $users = $d['users'];
    }
}

// Helper: check password, support hashed or legacy-plaintext
function verify_user_password(array &$users, string $username, string $password, string $dataFile): bool {
    if (!isset($users[$username])) return false;
    $stored = $users[$username];
    // If stored looks like a password_hash() (starts with $), try password_verify
    if (is_string($stored) && strlen($stored) > 0 && ($stored[0] === '$')) {
        if (password_verify($password, $stored)) return true;
        return false;
    }
    // legacy plaintext: use hash_equals for timing-safe compare; migrate on success
    if (hash_equals((string)$stored, $password)) {
        // migrate to hash if possible (persist)
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $users[$username] = $hash;
        // attempt to write back (if file exists/writable)
        if (is_dir(dirname($dataFile))) {
            $d = @json_decode(@file_get_contents($dataFile) ?: '', true);
            if (!is_array($d)) $d = ['games' => [], 'votes' => [], 'comments' => [], 'users' => []];
            $d['users'][$username] = $hash;
            @file_put_contents($dataFile . '.tmp', json_encode($d, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
            @rename($dataFile . '.tmp', $dataFile);
        }
        return true;
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim((string)($_POST['username'] ?? ''));
    $p = (string)($_POST['password'] ?? '');
    if ($u === '' || $p === '') {
        $error = 'Invalid username or password.';
    } else {
        // refresh users from disk if available to pick up admin-created users
        if (is_dir($dataDir)) {
            $d = load_data_file($dataFile);
            if (!empty($d['users'])) $users = $d['users'];
        }
        if (verify_user_password($users, $u, $p, $dataFile)) {
            session_regenerate_id(true);
            $_SESSION['user'] = $u;
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Games – Login</title>
  <style>
    body{font-family:system-ui,Segoe UI,Roboto,Arial;background:#f5f7fb;color:#111;margin:0;padding:24px}
    .box{max-width:380px;margin:40px auto;background:#fff;padding:20px;border-radius:10px;border:1px solid #e6edf3}
    label{display:block;margin-bottom:6px;color:#556}
    input[type=text],input[type=password]{width:100%;padding:8px;border-radius:6px;border:1px solid #ccd6df;margin-bottom:10px}
    button{background:#2563eb;color:#fff;border:0;padding:10px 14px;border-radius:8px;cursor:pointer}
    .error{color:#a00;margin-bottom:10px}
  </style>
</head>
<body>
  <div class="box">
    <h1>Login</h1>
    <?php if($error): ?><div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE); ?></div><?php endif; ?>
    <form method="post" action="login.php">
      <label for="username">Username</label>
      <input id="username" name="username" type="text" autocomplete="username" required />
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required />
      <div style="margin-top:10px"><button type="submit">Sign in</button></div>
    </form>
  </div>
</body>
</html>
