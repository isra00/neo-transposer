<?php

namespace NeoTransposer\Domain\Entity;

class UserPerformance
{
    protected $reports;
    protected $score;

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