<?php

namespace NeoTransposer\ChordPrinter;

class ChordPrinterSpanish extends ChordPrinter
{
	protected $cssClass = 'chord-sans';

	public function printChordInNotation($fundamental, $attributes)
	{
		$print_attributes = str_replace(
			array('m'),
			array('-'),
			$attributes
		);

		return \NeoTransposer\NotesCalculator::getNotation($fundamental, 'latin') . $print_attributes;
	}
}