<?php

namespace NeoTransposer;

include 'init.php';

$at = new AutomaticTransposer;
$scale = $at->numbered_scale;

$accoustic_scale = $at->accoustic_scale;

include 'wizard.view.php';