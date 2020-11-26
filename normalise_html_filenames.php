<?php
// Change names of old files in archive to conform to new naming pattern.
$input_dir = "public/archives/";
$Directory = new RecursiveDirectoryIterator($input_dir);
$Iterator = new RecursiveIteratorIterator($Directory);
$Regex = new RegexIterator($Iterator, '/^.+\.html$/i', RecursiveRegexIterator::GET_MATCH);
$files = array();
foreach($Regex as $filepath){
    array_push($files, $filepath[0]);
}
foreach ($files as $file) {
    print_r("Old name:    " . $file . "\n");
    $pathinfo = pathinfo($file);
    $dirname = $pathinfo['dirname'];
    $dirs = explode(DIRECTORY_SEPARATOR, $dirname);
    $year = substr($dirs[3], 2, 2);
    $month = $dirs[4];
    $day = $dirs[5];
    $newname = $dirname . DIRECTORY_SEPARATOR . $day . $month . $year . " " . $pathinfo['basename'];
    print_r("Renaming to: " . $newname . "\n");
    rename($file, $newname);
    print_r("\n");
}
?>