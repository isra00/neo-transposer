<?php

namespace NeoTransposerApp\Domain\ValueObject;

final class UserPerformance
{
    private $reports;
    private $score;

    public function __construct(int $reports, float $score)
    {
        $this->reports = $reports;
        $this->score = $score;
    }

    public function reports(): ?int
    {
        return $this->reports;
    }

    public function score(): ?float
    {
        return $this->score;
    }
}
