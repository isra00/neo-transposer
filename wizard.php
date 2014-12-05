<?php

include 'init.php';

require_once 'AutomaticTransposer.php';
$at = new AutomaticTransposer;
$scale = $at->numbered_scale;

include 'wizard.view.php';