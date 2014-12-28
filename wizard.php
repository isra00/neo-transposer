<?php

namespace NeoTransposer;

include 'init.php';

$nc = new NotesCalculator;
$scale = $nc->numbered_scale;
$accoustic_scale = $nc->accoustic_scale;

$page_class = 'page-wizard';
include 'wizard.view.php';