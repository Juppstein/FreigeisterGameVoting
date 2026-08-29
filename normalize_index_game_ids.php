<?php
// normalize_index_game_ids.php
// Scan index.php for data-community-game attributes and replace display values with slug ids
// Creates a timestamped backup of index.php before writing.
declare(strict_types=1);

$index = __DIR__ . '/index.php';
if (!file_exists($index)) { fwrite(STDERR, "index.php not found\n"); exit(1); }
$orig = file_get_contents($index);

// slugify (Unicode-aware where mbstring available)
$slugify = function(string $s): string {
    if (function_exists('mb_strtolower')) {
        $s = mb_strtolower($s, 'UTF-8');
    } else {
        $s = strtolower($s);
    }
    // replace non a-z0-9 with hyphen
    $s = preg_replace('/[^a-z0-9]+/u', '-', $s);
    $s = trim($s, '-');
    if ($s === '') {
        $s = bin2hex(random_bytes(4));
    }
    return substr($s, 0, 60);
};

$pattern = '/data-community-game=([\'"])(.*?)\\1/';
$replacements = 0;
$modified = preg_replace_callback($pattern, function($m) use ($slugify, &$replacements) {
    $origVal = $m[2];
    $slug = $slugify($origVal);
    $replacements++;
    return 'data-community-game="' . $slug . '"';
}, $orig, -1, $replacements);

$bak = $index . '.bak.' . date('YmdHis');
@copy($index, $bak) || fwrite(STDERR, "Warning: could not create backup $bak\n");
if ($replacements > 0) {
    if (@file_put_contents($index, $modified) === false) {
        fwrite(STDERR, "Failed to write index.php\n"); exit(1);
    }
    fwrite(STDOUT, "Normalized $replacements attributes. Backup: $bak\n");
} else {
    fwrite(STDOUT, "No data-community-game attributes found to replace.\n");
}