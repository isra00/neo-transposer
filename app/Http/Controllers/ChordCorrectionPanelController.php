<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use NeoTransposer\Domain\AdminTasks\CheckChordsOrder;

final class ChordCorrectionPanelController extends Controller
{
    public function get(CheckChordsOrder $checkChordsOrder)
    {
        $problematic = $checkChordsOrder->checkChordOrderArray();

        if (!$problematic) {
            return response('No inconsistent chord positions found :-)');
        }

        $idsToShow = array_slice(array_map('intval', array_keys($problematic)), 0, 10);

        $chords = DB::select(
            'SELECT * FROM song_chord JOIN song USING (id_song) WHERE id_song IN (' . implode(',', $idsToShow) . ') ORDER BY id_song, position'
        );

        $songs = [];
        foreach ($chords as $chord) {
            $chord = (array) $chord;

            if (!isset($songs[$chord['id_song']])) {
                $songs[$chord['id_song']] = [
                    'id_song' => $chord['id_song'],
                    'id_book' => $chord['id_book'],
                    'page'    => $chord['page'],
                    'title'   => $chord['title'],
                    'image'   => ($chord['id_book'] == 1)
                        ? "/resucito-imgs/sw/{$chord['page']}.jpg"
                        : '/resucito-imgs/es/' . str_pad((string) $chord['page'], 3, '0', STR_PAD_LEFT) . '.pdf',
                    'chords'  => [],
                ];
            }

            $songs[$chord['id_song']]['chords'][] = [
                'chord'    => $chord['chord'],
                'position' => $chord['position'],
            ];
        }

        return response()->view('chord_correction_panel', [
            'songs'      => $songs,
            'total'      => count($problematic),
            'page_title' => 'Chord correction panel',
            'page_class' => 'admin-dashboard',
        ]);
    }

    public function post(Request $request)
    {
        foreach ($request->except('_token', 'sent') as $key => $position) {
            if (preg_match('/^(\d+)_(.*)$/', $key, $match)) {
                $idSong = (int) $match[1];
                $chord = $match[2];

                DB::update(
                    'UPDATE song_chord SET position = ? WHERE id_song = ? AND chord = ?',
                    [(int) $position, $idSong, $chord]
                );
            }
        }

        return redirect()->route('chord_correction_panel');
    }
}
