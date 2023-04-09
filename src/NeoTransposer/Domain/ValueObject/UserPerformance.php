<?php

namespace NeoTransposer\Domain\ValueObject;

class UserPerformance
{
    public function __construct(
        protected int $reports,
        protected float $score)
    {
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