<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\Model\{TransposedSong,
    NotesRange,
    TransposedSongFactory,
    TranspositionChart,
    NotesCalculator,
    PeopleCompatibleCalculation};
use \Symfony\Component\HttpFoundation\Request;
use \NeoTransposer\NeoApp;

/**
 * Transpose Song page: transpose the given song for the singer's voice range.
 */
class TransposeSong
{
	public function get(NeoApp $app, Request $req, $id_song)
	{
		//For the teaser (not logged in), transpose for a standard male voice
		if (!$app['neouser']->isLoggedIn())
		{
			$app['neouser']->range = new NotesRange('B1', 'F#3');
		}
        //If null user, redirect to User Settings
		elseif (empty($app['neouser']->range->lowest))
        {
            $app->setLocaleAutodetect($req);

            return $app->redirect($app->path(
                'user_voice',
                array('_locale' => $app['locale'])
            ));
		}

        $transposedSongFactory = new TransposedSongFactory($app);
        $transposedSong = $transposedSongFactory->createTransposedSongFromSongId($id_song);

		$app['locale'] = $transposedSong->song->bookLocale;
		$app['translator']->setLocale($app['locale']);

		$transposedSong->transpose();

		$your_voice = $app['neouser']->getVoiceAsString(
			$app['translator'],
			$app['neoconfig']['languages'][$app['locale']]['notation']
		);

		$nc = new NotesCalculator();

		$transpositionChart = $this->generateTranspositionChart($nc, $app, $transposedSong);

		$tplVars = [];

		if ($transposedSong->peopleCompatible)
		{
			$difference = ($transposedSong->peopleCompatible->deviationFromCentered > 0) 
				? $app->trans('higher') 
				: $app->trans('lower');
			
			if (PeopleCompatibleCalculation::ADJUSTED_WIDER == $transposedSong->peopleCompatibleStatus)
			{
                /** @todo En vez de hacer esta var nueva, manipular directamente $tplVars['peopleCompatibleMsg'] */
				$peopleCompatibleMsg = $app->trans(
					'This other transposition, though a bit %difference%, may probably fit better the people of the assembly.', 
					['%difference%' => $difference]
				);
			}
			
			if (PeopleCompatibleCalculation::ADJUSTED_WELL == $transposedSong->peopleCompatibleStatus)
			{
				$peopleCompatibleMsg = $app->trans(
					'This other transposition, though a bit %difference%, fits well the people of the assembly.', 
					['%difference%' => $difference]
				);

				$tplVars['peopleCompatibleClass'] = 'star';
			}

			if (PeopleCompatibleCalculation::TOO_HIGH_FOR_PEOPLE == $transposedSong->peopleCompatibleStatus)
			{
				$peopleCompatibleMsg = $app->trans(
					'The chords given above are good for your voice, but probably too high for the assembly. The following transposition is %difference%, though still high for some people of the assembly.', 
					['%difference%' => $difference]
				);
			}

			if (PeopleCompatibleCalculation::TOO_LOW_FOR_PEOPLE == $transposedSong->peopleCompatibleStatus)
			{
				$peopleCompatibleMsg = $app->trans(
					'The chords given above are good for your voice, but probably too low for the assembly. The following transposition is %difference%, though still low for some people of the assembly.', 
					['%difference%' => $difference]
				);
			}

			if ($transposedSong->peopleCompatible->score < $transposedSong->transpositions[0]->score)
			{
				$peopleCompatibleMsg .= ' ' . $app->trans('And it has easier chords!');
			}

			$tplVars['peopleCompatibleMsg'] = $peopleCompatibleMsg;
		}

		if (0 === strpos($req->headers->get('Accept'), 'application/json'))
		{
			$transposeSongApi = new TransposeSongApi($app);
			return $transposeSongApi->handleApiRequest($req, $id_song);
		}

		return $app->render('transpose_song.twig', array_merge($tplVars, [
			'song'				=> $transposedSong,
			'your_voice'		=> $your_voice,
			'voice_chart'		=> $transpositionChart->getChart(),
			'page_title'		=> $app->trans('%song% (Neocatechumenal Way)', array('%song%' => $transposedSong->song->title)),
			'header_link'		=> $app->path('book_' . $transposedSong->song->idBook),
			'meta_canonical'	=> $app->url('transpose_song', ['id_song' => $transposedSong->song->slug]),
			'meta_description'	=> $app->trans(
				'Transpose the chords of &quot;%song%&quot; (song of the Neocatechumenal Way) automatically so you can sing it without stress!',
				['%song%' => $transposedSong->song->title]
			),
			'feedback'			=> $this->getFeedbackForUser($app['db'], $app['neouser']->id_user, $transposedSong->song->idSong),

			'user_less_than_one_octave' => $nc->rangeWideness($app['neouser']->range) < 12,
			'url_wizard' 		=> $app->path('wizard_step1', ['_locale' => $app['locale']]),

			//Non-JS browsers show message after clicking on feedback
			'non_js_fb'			=>  $req->get('fb')
		]));
	}

    /** @todo Refactor: quizá toda esta lógica puede ir en TranspositionChart y recibir solo el objeto TransposedSong */
	protected function generateTranspositionChart(NotesCalculator $nc, NeoApp $app, TransposedSong $transposedSong) : TranspositionChart
	{
		$transpositionChart = new TranspositionChart($nc, $transposedSong->song, $app['neouser'], $app['neoconfig']['languages'][$app['locale']]['notation']);
		$transpositionChart->addTransposition(
			'Transposed:', 
			'transposed-song', 
			$transposedSong->transpositions[0]
		);

		if ($app['neoconfig']['people_compatible'])
		{
			if ($transposedSong->song->peopleRange)
			{
				$transpositionChart->addVoice('Original for people:', 'original-song original-people', $transposedSong->song->peopleRange);
                /** @todo Este $nc->transposeRange no se debería estar haciendo aquí, sino que la Transposition ya debería tenerlo guardado */
				$transpositionChart->addVoice('Transposed for people:', 'transposed-song transposed-people', $nc->transposeRange($transposedSong->song->peopleRange, $transposedSong->transpositions[0]->offset));
				$transpositionChart->addVoice('People standard:', 'people-standard', new NotesRange($app['neoconfig']['people_range'][0], $app['neoconfig']['people_range'][1]));
			}
			
			if ($transposedSong->peopleCompatible)
			{
				$transpositionChart->addTransposition('Adjusted for you:', 'transposed-song', $transposedSong->peopleCompatible);
				$transpositionChart->addVoice('Adjusted for people:', 'people-compatible', $transposedSong->peopleCompatible->peopleRange);
			}
		}

		return $transpositionChart;
	}

	protected function getFeedbackForUser(\Doctrine\DBAL\Connection $db, $id_user, $id_song)
	{
		$worked = $db->fetchColumn(
			'SELECT worked FROM transposition_feedback WHERE id_user = ? AND id_song = ?',
			array($id_user, $id_song)
		);
		return str_replace(array('1', '0'), array('yes', 'no'), $worked);
	}
}
