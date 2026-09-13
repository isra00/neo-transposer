<?php

namespace NeoTransposer\Domain\AdminTasks;

final class CheckMissingTranslations implements AdminTask
{
    public function __construct(protected array $languagesConfig)
    {
    }

    public function run(): string
    {
        $langPath = base_path('lang');
        $isNotComment = fn ($key) => !str_starts_with($key, '/*');
        $spanishKeys = array_filter(array_keys(json_decode(file_get_contents("$langPath/es.json"), true)), $isNotComment);

        $diff = [];
        foreach ($this->languagesConfig as $lang => $langDetails) {
            if ($lang != 'es' && file_exists("$langPath/$lang.json")) {
                $trans = array_filter(array_keys(json_decode(file_get_contents("$langPath/$lang.json"), true)), $isNotComment);
                $diff[$lang] = array_diff($spanishKeys, $trans);
            }
        }

        return "TRANSLATION STRINGS IN SPANISH BUT NOT IN OTHER LANGUAGES:\n\n" . print_r($diff, true);
    }
}
