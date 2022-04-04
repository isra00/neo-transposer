<?php

namespace NeoTransposer\Domain\Service;

use NeoTransposer\Domain\Repository;

class AdminMetricsReader
{
    public const DETAILED_FB_DEPLOYED = '2017-08-11';

    protected $adminMetricsRepository;
    protected $allBooks;
    protected $geoIpResolver;

    public function __construct(Repository\AdminMetricsRepository $adminMetricsRepository, array $allBooks, \NeoTransposer\Domain\GeoIp\GeoIpResolver $geoIpResolver)
    {
        $this->adminMetricsRepository = $adminMetricsRepository;
        $this->allBooks = $allBooks;
        $this->geoIpResolver = $geoIpResolver;
    }

    public function readAdminMetrics(bool $longReports): array
    {
        $userCountTotal = $this->adminMetricsRepository->readUserCountTotal();

        return [
			'user_count'			=> $userCountTotal,
            'good_users'			=> $this->adminMetricsRepository->readUserCountGood(),
			'song_availability'		=> $this->adminMetricsRepository->readSongAvailability(),
			'global_performance'	=> $this->adminMetricsRepository->readGlobalPerformance(),
			'users_reporting_fb'	=> $this->adminMetricsRepository->readUsersReportingFeedback(),
			'unhappy_users'			=> $this->adminMetricsRepository->readUnhappyUsers(),
			'songs_with_fb'			=> $this->adminMetricsRepository->readSongsWithFeedback(),
			'most_active_users'		=> $longReports ? $this->adminMetricsRepository->readMostActiveUsers() : [],
			'perf_by_country'		=> $this->adminMetricsRepository->readPerformanceByCountry($this->geoIpResolver),
			'global_perf_chrono'	=> $longReports ? $this->adminMetricsRepository->readGlobalPerfChronological() : null,
			'feedback'				=> $longReports ? $this->adminMetricsRepository->readFeedback() : [],
			'good_users_chrono'		=> $longReports ? $this->adminMetricsRepository->readGoodUsersChronological() : null,
			'countries'				=> $this->adminMetricsRepository->readCountryNamesList($this->geoIpResolver),
			'dfb_transposition'		=> $this->adminMetricsRepository->readDetailedFeedbackTransposition(self::DETAILED_FB_DEPLOYED),
			'dfb_pc_status'			=> $this->adminMetricsRepository->readDetailedFeedbackPcStatus(),
			'dfb_centered_scorerate'=> $this->adminMetricsRepository->readDetailedFeedbackCenteredScoreRate(),
			'dfb_deviation'			=> $this->adminMetricsRepository->readDetailedFeedbackDeviation(),
			'usersByBook'			=> $this->adminMetricsRepository->readUsersByBook($userCountTotal),
			'performanceByBook'		=> $this->adminMetricsRepository->readPerformanceByBook($this->allBooks),
			'performanceByVoice'	=> $this->adminMetricsRepository->readPerformanceByVoice()
        ];
    }
}