<?php

namespace NeoTransposer\Tests\Application;

use NeoTransposer\Application\ReadAdminMetrics;
use NeoTransposer\Domain\AdminMetricsReader;
use NeoTransposer\Domain\GeoIp\Country;
use NeoTransposer\Domain\GeoIp\GeoIpLocation;
use NeoTransposer\Domain\GeoIp\GeoIpResolver;
use NeoTransposer\Domain\Repository\AdminMetricsRepository;
use PHPUnit\Framework\TestCase;

class ReadAdminMetricsTest extends TestCase
{
    /**
     * Outside-in test for the use case, domain service and mocked repository
     */
    public function testReadAdminMetricsNoLongReports()
    {
        $mockAdminMetricsRepository = $this->createMock(AdminMetricsRepository::class);
        $mockAdminMetricsRepository->method('readUserCountTotal')
            ->willReturn(123);
        $mockAdminMetricsRepository->method('readUserCountGood')
            ->willReturn(456);
        $mockAdminMetricsRepository->method('readSongAvailability')
            ->willReturn(['theReadSongAvailability']);
        $mockAdminMetricsRepository->method('readGlobalPerformance')
            ->willReturn(['theReadGlobalPerformance']);
        $mockAdminMetricsRepository->method('readUsersReportingFeedback')
            ->willReturn(['theReadUsersReportingFeedback']);
        $mockAdminMetricsRepository->method('readUnhappyUsers')
            ->willReturn(['theReadUnhappyUsers']);
        $mockAdminMetricsRepository->method('readSongsWithFeedback')
            ->willReturn(['theReadSongsWithFeedback']);
        $mockAdminMetricsRepository->method('readPerformanceByCountry')
            ->willReturn(['theReadPerformanceByCountry']);
        $mockAdminMetricsRepository->method('readCountryNamesList')
            ->willReturn(['theReadCountryNamesList']);
        $mockAdminMetricsRepository->method('readDetailedFeedbackTransposition')
            ->willReturn(['theReadDetailedFeedbackTransposition']);
        $mockAdminMetricsRepository->method('readDetailedFeedbackPcStatus')
            ->willReturn(['theReadDetailedFeedbackPcStatus']);
        $mockAdminMetricsRepository->method('readDetailedFeedbackCenteredScoreRate')
            ->willReturn(['theReadDetailedFeedbackCenteredScoreRate']);
        $mockAdminMetricsRepository->method('readDetailedFeedbackDeviation')
            ->willReturn(['theReadDetailedFeedbackDeviation']);
        $mockAdminMetricsRepository->method('readUsersByBook')
            ->willReturn(['theReadUsersByBook']);
        $mockAdminMetricsRepository->method('readPerformanceByBook')
            ->willReturn(['theReadPerformanceByBook']);
        $mockAdminMetricsRepository->method('readPerformanceByVoice')
            ->willReturn(['theReadPerformanceByVoice']);

        $mockAllBooks = [
            1 => [
                'id_book'       => '1',
                'lang_name'     => 'Kiswahili',
                'details'       => 'Tanzania - Kenya 2003',
                'chord_printer' => 'Swahili',
                'locale'        => 'sw',
                'song_count'    => '227',
            ]
        ];

        $mockGeoIpResolver = $this->createMock(GeoIpResolver::class);
        $mockGeoIpResolver->method('resolve')
            ->with('1.1.1.1')
            ->willReturn(new GeoIpLocation(new Country('TK', ['en' => 'Turkey'])));

        $realDomainService  = new AdminMetricsReader($mockAdminMetricsRepository, $mockAllBooks, $mockGeoIpResolver);
        $sut = new ReadAdminMetrics($realDomainService);

        $expected = [
            'user_count'			=> 123,
            'good_users'			=> 456,
            'song_availability'		=> ['theReadSongAvailability'],
            'global_performance'	=> ['theReadGlobalPerformance'],
            'users_reporting_fb'	=> ['theReadUsersReportingFeedback'],
            'unhappy_users'			=> ['theReadUnhappyUsers'],
            'songs_with_fb'			=> ['theReadSongsWithFeedback'],
            'most_active_users'	    => [],
            'perf_by_country'		=> ['theReadPerformanceByCountry'],
            'global_perf_chrono'    => null,
            'feedback'              => [],
            'good_users_chrono'     => null,
            'countries'				=> ['theReadCountryNamesList'],
            'dfb_transposition'		=> ['theReadDetailedFeedbackTransposition'],
            'dfb_pc_status'			=> ['theReadDetailedFeedbackPcStatus'],
            'dfb_centered_scorerate'=> ['theReadDetailedFeedbackCenteredScoreRate'],
            'dfb_deviation'			=> ['theReadDetailedFeedbackDeviation'],
            'usersByBook'			=> ['theReadUsersByBook'],
            'performanceByBook'		=> ['theReadPerformanceByBook'],
            'performanceByVoice'	=> ['theReadPerformanceByVoice'],
        ];

        $this->assertEquals($expected, $sut->readAdminMetrics(false));
    }
}
