<?php

namespace NeoTransposer\Domain\AdminTasks;

use Doctrine\DBAL\Connection;

class CheckMissingTranslations implements AdminTask
{
    protected $languagesConfig;

    public function __construct(array $languagesConfig)
    {
        $this->languagesConfig = $languagesConfig;
    }

	public function run(): string
	{
		$transSpanish = include $this->languagesConfig['es']['file'];

		$diff = [];
		foreach ($this->languagesConfig as $lang=>$langDetails)
		{
			if (isset($langDetails['file']) && 'es' != $lang)
			{
				$trans = include $langDetails['file'];
				$diff[$lang] = array_diff(array_keys($transSpanish), array_keys($trans));
			}
		}

		return "TRANSLATION STRINGS IN SPANISH BUT NOT IN OTHER LANGUAGES:\n\n" . print_r($diff, true);
	}
}
