<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WebManifestController extends AbstractController
{
    #[Route('/{_locale}/manifest.json', name: 'webmanifest')]
    public function get(TranslatorInterface $translator): Response
    {
        $json = [
            "name"             => $translator->trans('Transpose the songs of the Neocatechumenal Way · Neo-Transposer'),
            "short_name"       => "Neo-Transposer",
            "description"      => $translator->trans(
                'Transpose the songs of the Neocatechumenal Way automatically with Neo-Transposer. The exact chords for your own voice!'
            ),
            "categories"       => "utilities",
            "background_color" => "#D32F2F",
            "theme_color"      => "#D32F2F",
            "display"          => "standalone",
            "lang"             => $translator->getLocale(),
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

        return new Response(
            json_encode($json),
            Response::HTTP_OK,
            ['Content-Type' => 'application/manifest+json']
        );
    }
}
