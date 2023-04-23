<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ManifestoController extends AbstractController
{
    #[Route('/{_locale}/manifesto', name: 'manifesto')]
    public function get(TranslatorInterface $translator): Response
    {
        return $this->render('pages/manifesto.es.twig', [
            'page_title' => $translator->trans('Manifiesto'),
        ]);
    }
}