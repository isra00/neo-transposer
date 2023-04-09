<?php

namespace NeoTransposer\Application;

use NeoTransposer\Domain\Service\AdminMetricsReader;

class ReadAdminMetrics
{
    public function __construct(protected AdminMetricsReader $adminMetricsReader)
    {
    }

    public function readAdminMetrics(bool $longReports): array
    {
        return $this->adminMetricsReader->readAdminMetrics($longReports);

    }
}