<?php

function checkOrCreateDirectory($path = false)
{
    $rootPath = dirname(__FILE__);
    $buildPath = $rootPath . "/../". (!empty($path) ? $path : "");
    if (!file_exists($buildPath)) {
        mkdir($path, 0777, true);
    }
    return $buildPath;
}

function writeFile($path, $filename, $content)
{
    checkOrCreateDirectory($path);
    file_put_contents($path . "/" . $filename, $content);
}
