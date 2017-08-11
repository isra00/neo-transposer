<?php

$contents = file_get_contents(__DIR__ . '/static/img/chords/' . $_GET['chord']);

header("Cache-Control: private");
header("Content-Type: image/png");
header("Content-Length: " . strlen($contents));
header("Content-Disposition: attachment; filename=" . $_GET['chord']);
//sleep(2);
die($contents);