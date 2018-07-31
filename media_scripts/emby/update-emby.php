<?php
$cmd = "curl -s https://api.github.com/repos/MediaBrowser/Emby.Releases/releases";
$json = json_decode(shell_exec($cmd));
$assets = $json[0]->assets;

$url = "";
foreach ($assets as $asset) {
    $name = $asset->name;
    if (stripos($name,"amd64.deb") === FALSE) {
        continue;
    }
    if (stripos($name,"netgear") !== FALSE) {
        continue;
    }
    $url = $asset->browser_download_url;
}

if (!$url) die("NO DEB 64-bit FILE FOUND!\n"); else echo "Found release: $url\n";

$fn = basename($url);

$cmds = [];
$cmds[] = "rm ~/Downloads/emby-*.*";
$cmds[] = "wget -O ~/Downloads/$fn $url";
$cmds[] = "sudo dpkg -i ~/Downloads/emby*.deb";
foreach ($cmds as $cmd) {
    echo "*************************************\n- $cmd\n";
    exec($cmd);
    echo "\n";
}
