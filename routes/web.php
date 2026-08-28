<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AllSongsReportController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ChordCorrectionPanelController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\InsertSongController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ReceiveFeedbackController;
use App\Http\Controllers\ServeCssController;
use App\Http\Controllers\SetUserDataController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TransposeSongController;
use App\Http\Controllers\UserBookController;
use App\Http\Controllers\UserVoiceController;
use App\Http\Controllers\WebManifestController;
use App\Http\Controllers\WizardEmpiricController;
use App\Http\Controllers\WizardSelectStandardController;
use App\Http\Middleware\AdminBasicAuth;
use App\Http\Middleware\NeedsLoginMiddleware;
use App\Http\Middleware\PreventResponseCaching;
use App\Http\Middleware\SetLocaleFromUrl;
use Illuminate\Support\Facades\Route;

Route::get('/', [IndexController::class, 'get']);

// SEO-friendly URLs for books
$bookUrls = [
    1 => '/nyimbo-njia-neokatekumenato',
    2 => '/cantos-camino-neocatecumenal',
    3 => '/songs-neocatechumenal-way',
    4 => '/cantos-caminho-neocatecumenal',
    5 => '/canti-cammino-neocatecumenale',
];

foreach ($bookUrls as $bookId => $slug) {
    Route::get($slug, [BookController::class, 'get'])->defaults('bookId', $bookId)->name('book_' . $bookId);
}

$validLocales = '(' . implode('|', array_keys(config('nt.languages'))) . ')';

Route::prefix('{locale}')
    ->where(['locale' => $validLocales])
    ->middleware([SetLocaleFromUrl::class])
    ->group(function () {

        // Login routes
        Route::get('/login', [LoginController::class, 'get'])->name('login');
        Route::post('/login', [LoginController::class, 'post']);

        Route::get('/manifest.json', [WebManifestController::class, 'get'])->name('webmanifest');

        Route::get('/commitment', function () {
            return response(view()->file(resource_path('views/pages/commitment.' . app()->getLocale() . '.blade.php'), [
                'page_title' => 'Compromiso de gratuidad',
                'page_class' => 'static-page',
            ]));
        })->where('locale', 'es')->name('commitment');

        Route::get('/manifesto', function () {
            return response(view()->file(resource_path('views/pages/manifesto.' . app()->getLocale() . '.blade.php'), [
                'page_title' => 'Manifiesto',
                'page_class' => 'static-page',
            ]));
        })->where('locale', 'es')->name('manifesto');

        Route::get('/people-compatible-transpositions', function () {
            $templatePath = resource_path('views/pages/people-compatible-info.' . app()->getLocale() . '.blade.php');

            if (!file_exists($templatePath)) {
                $templatePath = resource_path('views/pages/people-compatible-info.en.blade.php');
            }

            return response(view()->file($templatePath, [
                'page_title' => __('People-compatible transpositions'),
                'page_class' => 'static-page',
            ]));
        })->name('people-compatible-info');

        Route::group(['middleware' => NeedsLoginMiddleware::class], function () {

            Route::get('/user/voice', [UserVoiceController::class, 'get'])
                ->name('user_voice');

            Route::get('/user/book', [UserBookController::class, 'get'])
                ->name('user_book');

            Route::get('/all-songs-report', [AllSongsReportController::class, 'get'])
                ->name('all_songs_report');

            Route::get('/wizard', [WizardSelectStandardController::class, 'get'])
                ->name('wizard_step1');

            Route::get('/wizard/select-standard', [WizardSelectStandardController::class, 'selectStandard'])
                ->name('wizard_select_standard');

            Route::match(['get', 'post'], '/wizard/lowest', [WizardEmpiricController::class, 'lowest'])
                ->name('wizard_empiric_lowest');

            Route::match(['get', 'post'], '/wizard/highest', [WizardEmpiricController::class, 'highest'])
                ->name('wizard_empiric_highest');
        });
    });

Route::group(['middleware' => NeedsLoginMiddleware::class], function () {

    Route::get('/set-user-data', [SetUserDataController::class, 'get'])
        ->name('set_user_data');
});

Route::get('/sitemap.xml', [SitemapController::class, 'get']);

// Lazily compiles /static/compiled-<hash>.css when Apache doesn't find it on disk.
Route::get('/static/compiled-{hash}.css', [ServeCssController::class, 'get'])
    ->where('hash', '[a-f0-9]{32}');

// Feedback route handles auth check itself (returns 408 for AJAX when session expires)
Route::post('/feedback', [ReceiveFeedbackController::class, 'post'])
    ->name('transposition_feedback');

Route::get('/transpose/{id_song}', [TransposeSongController::class, 'get'])
    ->name('transpose_song');

// Admin routes
// PreventResponseCaching is listed first so it also covers AdminBasicAuth's 401 response.
Route::middleware([
    PreventResponseCaching::class,
    AdminBasicAuth::class,
])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'get'])
        ->name('admin_dashboard');

    Route::get('/admin/chord-correction', [ChordCorrectionPanelController::class, 'get'])
        ->name('chord_correction_panel');
    Route::post('/admin/chord-correction', [ChordCorrectionPanelController::class, 'post']);

    Route::get('/admin/insert-song', [InsertSongController::class, 'get'])
        ->name('insert_song');
    Route::post('/admin/insert-song', [InsertSongController::class, 'post']);
});

// Easter eggs ;-)
Route::get('/get-lucky', [TransposeSongController::class, 'get'])
    ->defaults('id_song', 118);

Route::get('/sura-yako', [TransposeSongController::class, 'get'])
    ->defaults('id_song', 319);
