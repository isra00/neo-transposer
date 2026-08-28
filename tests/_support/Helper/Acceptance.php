<?php

namespace Helper;

use Codeception\Module;
use Facebook\WebDriver\Remote\RemoteWebDriver;

/**
 * Codeception/RemoteWebDriver extension for allowing setting custom HTTP headers for acceptance tests.
 */
class Acceptance extends Module
{
    public function haveHttpHeader(string $name, string $value): void
    {
        $this->getModule('WebDriver')->executeInSelenium(
            function (RemoteWebDriver $driver) use ($name, $value) {
                $this->sendCdpCommand($driver, 'Network.enable', (object) []);
                $this->sendCdpCommand($driver, 'Network.setExtraHTTPHeaders', ['headers' => [$name => $value]]);
            }
        );
    }

    private function sendCdpCommand(RemoteWebDriver $driver, string $command, $params): void
    {
        $driver->executeCustomCommand(
            '/session/:sessionId/goog/cdp/execute',
            'POST',
            ['cmd' => $command, 'params' => $params]
        );
    }
}
