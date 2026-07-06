<?php
function searchDir($dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            searchDir($path);
        } else {
            $c = file_get_contents($path);
            if (str_contains($c, 'canAccessPanel') || str_contains($c, 'FilamentUser')) {
                echo "FOUND: $path\n";
            }
        }
    }
}
searchDir('app');
