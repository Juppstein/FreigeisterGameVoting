<?php
// merge_liste2_into_liste1.php
// Merge Liste 2 MMO entries into Liste 1 in community-data/community.json
// Creates a timestamped backup and writes the updated file.
declare(strict_types=1);

$dir = __DIR__ . '/community-data';
$file = $dir . '/community.json';
if (!is_dir($dir)) { fwrite(STDERR, "Missing directory: $dir\n"); exit(1); }
if (!file_exists($file)) { fwrite(STDERR, "Missing data file: $file\n"); exit(1); }

$raw = file_get_contents($file);
$data = json_decode($raw, true);
if (!is_array($data)) { fwrite(STDERR, "Invalid JSON in $file\n"); exit(1); }
if (!isset($data['games']) || !is_array($data['games'])) $data['games'] = [];

// Liste 2 → genre mapping (Normal group structure column)
$map = [
  "everquest-ii" => "6-player group",
  "the-lord-of-the-rings-online" => "6-player Fellowship",
  "final-fantasy-xiv-2" => "4/8-player party",
  "the-elder-scrolls-online-2" => "4-player groups / 12-player Trials",
  "guild-wars-2" => "5-player party / 50-player squad",
  "eve-online" => "Fleets",
  "old-school-runescape" => "Flexible",
  "albion-online" => "Flexible / large groups",
  "star-wars-the-old-republic" => "4/8/12-player group",
  "lost-ark" => "4/8-player content",
  "ashes-of-creation" => "6/12/24",
  "throne-of-liberty" => "6/12/24",
  "pantheon-rise-of-the-fallen" => "Everquest Style",
  "monsters-and-memories" => "Old School MMO Style"
];

$added = $updated = 0;
foreach ($map as $id => $genre) {
  if (!isset($data['games'][$id])) {
    // create minimal entry
    $data['games'][$id] = [
      'id' => $id,
      'name' => ucwords(str_replace(['-'], ' ', $id)),
      'players' => '',
      'genre' => $genre,
      'steam' => '',
      'notes' => '',
      'list' => 1
    ];
    $added++;
  } else {
    $data['games'][$id]['genre'] = $genre;
    $data['games'][$id]['list'] = 1;
    $updated++;
  }
}

// backup and save
$bak = $file . '.bak.' . date('YmdHis');
@copy($file, $bak);
$tmp = $file . '.tmp-' . bin2hex(random_bytes(6));
if (@file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) {
  fwrite(STDERR, "Failed to write temp file\n"); exit(1);
}
if (!@rename($tmp, $file)) { fwrite(STDERR, "Failed to move tmp->final\n"); exit(1); }
fwrite(STDOUT, "Merge complete. Added: $added, Updated: $updated. Backup: $bak\n");