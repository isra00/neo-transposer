<?php

namespace NeoTransposer\Domain;

class UserNotExistException extends \Exception
{
    public function __construct(int $idUser)
    {
        $this->message = sprintf('The user #%s has not been found', $idUser);
    }
}