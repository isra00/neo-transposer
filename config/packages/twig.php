<?php

use Symfony\Config\TwigConfig;

return static function (TwigConfig $twig) {
    $emptyUser = new App\Domain\Entity\User();
    //$twig->global('neouser')->value($emptyUser);
};