<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

final class WebManifest extends Controller
{
    public function get(): JsonResponse
    {
        $json = [
            "name"             => __('Transpose the songs of the Neocatechumenal Way · Neo-Transposer'),
            "short_name"       => "Neo-Transposer",
            "description"      => __(
                'Transpose the songs of the Neocatechumenal Way automatically with Neo-Transposer. The exact chords for your own voice!'
            ),
            "categories"       => "utilities",
            "background_color" => "#D32F2F",
            "theme_color"      => "#D32F2F",
            "display"          => "standalone",
            "lang"             => App::getLocale(),
            "start_url"        => "/",
            "icons"            => [
                [
                    "src"     => "/static/img/icon/source/logo-red-maskable.svg",
                    "sizes"   => "512x512",
                    "type"    => "image/svg+xml",
                    "purpose" => "any maskable"
                ],
                [
                    "src"     => "/static/img/icon-192x192.png",
                    "sizes"   => "192x192",
                    "type"    => "image/png",
                    "purpose" => "any maskable"
                ],
                [
                    "src"     => "/static/img/icon-512x512.png",
                    "sizes"   => "512x512",
                    "type"    => "image/png",
                    "purpose" => "any maskable"
                ],
                [
                    "src"     => "/static/img/apple-touch-icon.png",
                    "sizes"   => "180x180",
                    "type"    => "image/png",
                    "purpose" => "any maskable"
                ]
            ]
        ];

        return response()->json($json);
    }
}
