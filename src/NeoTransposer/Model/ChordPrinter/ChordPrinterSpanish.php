<?php

namespace NeoTransposer\Model\ChordPrinter;

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

		return \NeoTransposer\Model\NotesNotation::getNotation($fundamental, 'latin') . $attributes;
	}
}