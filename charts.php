<?php

$charts = scandir('/home/isra/Documentos/Camino Neocatecumenal/Cantos del Camino/Español/prontuario');

$testResults = json_decode(file_get_contents('tests/testAllTranspositions.expected.json'), true);
$testResults = $testResults['expectedResults'];

array_walk($charts, function(&$filename) {
	$filename = substr($filename, 0, strlen($filename) - 4);
});

$allChordsInTestResults = [];

foreach ($testResults as $song)
{
	$transpositions = ['centered1', 'centered2'];
	if (isset($song['notEquivalent']))
	{
		$transpositions[] = 'notEquivalent';
	}

	foreach ($transpositions as $trans)
	{
		
		$chordList = explode(',', $song[$trans]['chords']);
		foreach ($chordList as $chord)
		{
			$allChordsInTestResults[$chord] = true;
		}
	}
}

$allChordsInTestResults = array_keys($allChordsInTestResults);

foreach ($allChordsInTestResults as $chord)
{
	if (false === array_search($chord, $charts))
	{
		echo "missing $chord\n";
	}
}
