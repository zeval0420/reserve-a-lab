<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
 
$games_dir = __DIR__;
$files = glob($games_dir . '/*.html');
 
$games = [];
foreach ($files as $file) {
    $filename = basename($file);
    // Convert filename to readable title: "snake_game.html" → "Snake Game"
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $title = ucwords(str_replace(['_', '-'], ' ', $name));
    $games[] = [
        'file'  => $filename,
        'title' => $title,
        'path'  => '../../beta/games/' . $filename,
    ];
}
 
echo json_encode($games);
