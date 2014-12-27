<?php

namespace NeoTransposer;

include 'init.php';

$nc = new NotesCalculator;
$scale = $nc->numbered_scale;
$accoustic_scale = $nc->accoustic_scale;

include 'wizard.view.php';