<?php

namespace NeoTransposerApp\Application;

use NeoTransposerApp\Domain\Service\AdminMetricsReader;

class ReadAdminMetrics
{
    protected $adminMetricsReader;

    public function __construct(AdminMetricsReader $adminMetricsReader)
    {
        $this->adminMetricsReader = $adminMetricsReader;
    }

    public function readAdminMetrics(bool $longReports): array
    {
        return $this->adminMetricsReader->readAdminMetrics($longReports);

    }
}