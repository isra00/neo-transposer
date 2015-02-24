<?php

return array(

	'es'	=> array(
		'lowest' => array(
			'id_song'		=> 255,
			'override_highest_note'	=> 'F2',
			'song_contents'	=> <<<SONG
%0                 %1
YAHVEH, TÚ ERES MI DIOS,

             %0
YO TE ENSALZARÉ.
SONG
		),

		'highest' => array(
			'id_song'		=> 236,
			'song_contents'	=> <<<SONG
%0                  %1     %2
SI EL SEÑOR NO CONSTRUYE LA CASA,

   %3                        %0
EN VANO SE CANSAN LOS CONSTRUCTORES
SONG
		),
	),

	'sw'	=> array(
		'lowest' => array(
			'id_song'				=> 44,
			'override_highest_note'	=> 'F2',
			'song_contents'			=> <<<SONG
%0             %1
YAHWEH U MUNGU WANGU

        %0
NITAKUTUKUZA
SONG
		),

		'highest' => array(
			'id_song'		=> 368,
			'song_contents'	=> <<<SONG
%0           %1    %2
BWANA ASIPOIJENGA NYUMBA

      %3                %0
WAIJENGAO WAFANYA KAZI BURE
SONG
		),
	),

	'standard_voices'	=> array(
		'male_high'		 => array('C#2', 'F#3'),
		'male'			 => array('B1',	 'E3'),
		'male_low'		 => array('A1',	 'D3'),
		'female'		 => array('E1',	 'A2'),
		'female_high'	 => array('F#1', 'C2'),
		'female_low'	 => array('D1',	 'G2'),
	),
);