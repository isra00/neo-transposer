<?php

namespace NeoTransposer\Application;

use NeoTransposer\Domain\AdminMetricsReader;

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