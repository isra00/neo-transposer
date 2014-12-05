<?php

require_once 'ChordPrinter.php';

class ChordPrinterEnglish extends ChordPrinter
{
	public function printChordInNotation($fundamental, $attributes)
	{
		if ($fundamental == 'A#')
		{
			$fundamental = 'B<em>b</em>';
		}

		$print_attributes = str_replace(
			array('4', '6', '7'),
			array('<sup>4</sup>', '<sup>6</sup>', '<sup>7</sup>'),
			$attributes
		);

		return $fundamental . $print_attributes;
	}
}