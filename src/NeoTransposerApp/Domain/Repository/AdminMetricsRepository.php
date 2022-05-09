<?php

namespace NeoTransposerApp\Domain\Repository;

interface AdminMetricsRepository
{
    public function readUserCountTotal(): int;
    public function readUserCountGood(): int;
    public function readGlobalPerformance(): array;
    public function readUsersReportingFeedback(): array;
    public function readSongAvailability(): array;
    public function readFeedback(): array;
    public function readUnhappyUsers(): array;
    public function readGlobalPerfChronological(): array;
    public function readSongsWithFeedback(): array;
    public function readMostActiveUsers(): array;
    public function readGoodUsersChronological(): array;
    public function readCountryNamesList(\NeoTransposerApp\Domain\GeoIp\GeoIpResolver $geoIpResolver): array;
    public function readPerformanceByCountry(\NeoTransposerApp\Domain\GeoIp\GeoIpResolver $geoIpResolver): array;
    public function readDetailedFeedbackTransposition(string $detailedFeedbackDeployed): array;
    public function readDetailedFeedbackPcStatus(): array;
    public function readDetailedFeedbackCenteredScoreRate(): array;
    public function readDetailedFeedbackDeviation(): array;
    public function readUsersByBook(int $totalUsers): array;
    public function readPerformanceByBook(array $allBooks): array;
    public function readPerformanceByVoice(): array;
}
