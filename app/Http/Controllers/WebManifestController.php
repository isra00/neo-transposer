<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

final class WebManifestController extends Controller
{
    public function get(): JsonResponse
    {
        $json = [
            "name"             => __('Transpose the songs of the Neocatechumenal Way · Neo-Transposer'),
            "short_name"       => "Neo-Transposer",
            "description"      => __('Transpose the songs of the Neocatechumenal Way automatically with Neo-Transposer. The exact chords for your own voice!'),
            "categories"       => ["utilities", "music"],
            "background_color" => "#D32F2F",
            "theme_color"      => "#D32F2F",
            "display"          => "standalone",
            "lang"             => App::getLocale(),
            "start_url"        => "/",
            "icons"            => [
                [
                    "src"     => "/static/img/logo-red.svg",
                    "sizes"   => "any",
                    "type"    => "image/svg+xml",
                    "purpose" => "any"
                ],
                [
                    "src"     => "/static/img/icon-192x192.png",
                    "sizes"   => "192x192",
                    "type"    => "image/png",
                    "purpose" => "any"
                ],
                [
                    "src"     => "/static/img/icon-512x512.png",
                    "sizes"   => "512x512",
                    "type"    => "image/png",
                    "purpose" => "any"
                ],
                [
                    "src"     => "/static/img/icon-maskable-192x192.png",
                    "sizes"   => "192x192",
                    "type"    => "image/png",
                    "purpose" => "maskable"
                ],
                [
                    "src"     => "/static/img/icon-maskable-512x512.png",
                    "sizes"   => "512x512",
                    "type"    => "image/png",
                    "purpose" => "maskable"
                ]
            ],
            "screenshots"      => [
                [
                    "src"          => "/static/img/screenshots/desktop-1.png",
                    "sizes"        => "1280x720",
                    "type"         => "image/png",
                    "form_factor"  => "wide",
                    "label"        => 'Neo-Transposer'
                ],
                [
                    "src"          => "/static/img/screenshots/mobile-1.png",
                    "sizes"        => "412x915",
                    "type"         => "image/png",
                    "form_factor"  => "narrow",
                    "label"        => 'Neo-Transposer'
                ]
            ]
        ];

        return response()->json($json);
    }
}
