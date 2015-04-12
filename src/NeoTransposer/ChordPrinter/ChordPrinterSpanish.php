<?php

namespace NeoTransposer\ChordPrinter;

class ChordPrinterSpanish extends ChordPrinter
{
	protected $cssClass = 'chord-sans';

	public function printChordInNotation($fundamental, $attributes)
	{
		if (false === strpos($attributes, 'dim'))
		{
			$attributes = str_replace(
				array('m', 'M'),
				array('-', 'aum'),
				$attributes
			);
		}

		return \NeoTransposer\Model\NotesCalculator::getNotation($fundamental, 'latin') . $attributes;
	}
}