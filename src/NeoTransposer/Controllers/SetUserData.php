<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\Domain\Exception\BadUserRangeException;
use NeoTransposer\Domain\Exception\BookNotExistException;
use NeoTransposer\Domain\Exception\InvalidStandardRangeException;
use NeoTransposer\Domain\Service\UserWriter;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request};
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Sets the user data and redirect. There is no response body.
 *
 * @todo Rename to UpdateUser
 */
final class SetUserData
{
	public function get(Request $request, \NeoTransposer\NeoApp $app): RedirectResponse
	{
        $userDataWriter = $app[UserWriter::class];

        try {
            $userDataWriter->writeUser(
                $app['neouser'],
                (int) $request->get('book'),
                $request->get('lowest_note'),
                $request->get('highest_note'),
                $request->get('unhappy_choose_std')
            );
        } catch (BookNotExistException $e) {
            throw new BadRequestHttpException('Invalid request: ' . $e->getMessage());
        } catch (BadUserRangeException)
        {
            return $app->redirect($app->path(
                'user_voice',
                ['bad_voice_range' => '1']
            ));
        } catch (InvalidStandardRangeException)
        {
            $app->abort(400, 'Bad value for URL parameter unhappy_choose_std');
        }

		return $app->redirect(
            $request->get('redirect') ?: $app->path('book_' . $app['neouser']->id_book)
		);
	}
}
