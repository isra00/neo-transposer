<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use NeoTransposer\Domain\Exception\BadUserRangeException;
use NeoTransposer\Domain\Exception\BookNotExistException;
use NeoTransposer\Domain\Exception\InvalidStandardRangeException;
use NeoTransposer\Domain\Service\UserWriter;

/**
 * Sets the user data and redirect. There is no response body.
 *
 * @todo Rename to UpdateUser
 */
final class SetUserDataController
{
	public function get(Request $request, UserWriter $userDataWriter)
	{
        try {
            $userDataWriter->writeUser(
                session('user'),
                (int) $request->query('book'),
                $request->query('lowest_note'),
                $request->query('highest_note'),
                $request->query('unhappy_choose_std')
            );
        } catch (BookNotExistException $e) {
            throw new BadRequestHttpException('Invalid request: ' . $e->getMessage());
        } catch (BadUserRangeException) {
            return redirect()->route('user_voice', ['bad_voice_range' => '1']);
        } catch (InvalidStandardRangeException) {
            abort(400, 'Bad value for URL parameter unhappy_choose_std');
        }

		return redirect(
            $request->query('redirect')
                ?: route('book_' . session('user')->id_book)
        );
	}
}
