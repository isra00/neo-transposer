<?php

require 'vendor/autoload.php';

$printer = new NeoTransposer\ChordPrinter\ChordPrinterSpanish;
echo $printer->printChord('A#');
die;

$loader = new Twig_Loader_Filesystem(__DIR__ . '/templates');
$twig = new Twig_Environment($loader, array(
    //'cache' => __DIR__ . '/tmp',
));

echo $twig->render('index2.tpl', array('hello' => 'world'));