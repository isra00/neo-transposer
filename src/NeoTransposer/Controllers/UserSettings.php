<?php

namespace NeoTransposer\Controllers;

use \NeoTransposer\AutomaticTransposer;
use \NeoTransposer\TranspositionChart;
use \NeoTransposer\NotesCalculator;

class UserSettings
{
	public function get(\NeoTransposer\NeoApp $app)
	{
		$nc = new NotesCalculator;

		return $app->render('user_settings.tpl', array(
			'page_title'		=> 'Settings',
			'scale'				=> $nc->numbered_scale,
			'accoustic_scale'	=> $nc->accoustic_scale
		));
	}
}