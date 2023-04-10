<?php

namespace NeoTransposer\Controllers;

use NeoTransposer\NeoApp;

final class WizardInstructions
{
    public function get(NeoApp $app): string
    {
        $app['neouser']->wizard_lowest_attempts = 0;
        $app['neouser']->wizard_highest_attempts = 0;

        return $app->render('wizard_empiric_instructions.twig');
    }
}