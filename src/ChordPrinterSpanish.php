<?php

require_once 'ChordPrinter.php';

class ChordPrinterSpanish extends ChordPrinter
{
	protected $cssClass = 'chord-sans';

	public function printChordInNotation($fundamental, $attributes)
	{
		$print_fundamentals = array(
			'A'		=> 'La',
			'A#'	=> 'Sib',
			'B' 	=> 'Si',
			'C'		=> 'Do',
			'C#'	=> 'Do#',
			'D'		=> 'Re',
			'D#'	=> 'Re#',
			'E'		=> 'Mi',
			'F'		=> 'Fa',
			'F#'	=> 'Fa#',
			'G'		=> 'Sol',
			'G#'	=> 'Sol#'
		);

		$print_attributes = str_replace(
			array('m'),
			array('-'),
			$attributes
		);

		return $print_fundamentals[$fundamental] . $print_attributes;
	}
}