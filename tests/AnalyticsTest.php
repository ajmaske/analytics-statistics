<?php

namespace Spatie\Analytics\Test;

use DateTime;
use Mockery;
use Spatie\Analytics\Analytics;

class AnalyticsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Mockery\MockInterface  Mock of \Spatie\Analytics\GoogleClient
     */
    protected $client;

    /**
     * @var \Spatie\Analytics\Analytics
     */
    protected $analytics;

    /**
     * @var string
     */
    protected $siteId;

    /**
     * @var \DateTime
     */
    protected $now;

    public function setUp()
    {
        $this->client = Mockery::mock('\Spatie\Analytics\GoogleClient');
        $this->siteId = '12345';
        $this->now = new DateTime;

        $this->analytics = new Analytics($this->client, $this->siteId);
    }

    public function testGetVisitorsAndPageViews()
    {
        $startDate = $this->now->modify('-1 year')->format('Y-m-d');
        $endDate = date('Y-m-d');

        $this->client
            ->shouldReceive('performQuery')
            ->with($this->siteId, $startDate, $endDate, "ga:visits,ga:pageviews",
                array(
                    'dimensions' => 'ga:date'
                )
            )
            ->andReturn((object) array('rows' => array(array('20140101', 2, 3))));

        $googleResult = $this->analytics->getVisitorsAndPageViews();

        $resultProperties = array('date', 'visitors', 'pageViews');

        $this->assertTrue(count($googleResult) === 1);

        foreach ($resultProperties as $property) {
            $this->assertArrayHasKey($property, $googleResult[0]);
        }
    }

    public function testGetTopKeywords()
    {
        $startDate = $this->now->modify('-1 year')->format('Y-m-d');
        $endDate = date('Y-m-d');

        $this->client
            ->shouldReceive('performQuery')
            ->with($this->siteId, $startDate, $endDate, "ga:sessions",
                array(
                    'dimensions' => 'ga:keyword',
                    'sort' => '-ga:sessions',
                    'max-results' => 30,
                    'filters' => 'ga:keyword!=(not set);ga:keyword!=(not provided)'
                )
            )
            ->andReturn((object) array('rows' => array(array('first', 'second'))));

        $googleResult = $this->analytics->getTopKeyWords();

        $this->assertEquals($googleResult, array(array('keyword' => 'first', 'sessions' => 'second')));
    }

    public function testGetTopReferrers()
    {
        $startDate = $this->now->modify('-1 year')->format('Y-m-d');
        $endDate = date('Y-m-d');

        $this->client
            ->shouldReceive('performQuery')
            ->with($this->siteId, $startDate, $endDate, "ga:pageviews",
                array(
                    'dimensions' => 'ga:fullReferrer',
                    'sort' => '-ga:pageviews',
                    'max-results' => 20
                )
            )
            ->andReturn((object) array('rows' => array(array('foundUrl', '123'))));

        $googleResult = $this->analytics->getTopReferrers();

        $this->assertEquals($googleResult, array(array('url' => 'foundUrl', 'pageViews' => '123')));
    }

    public function testGetTopBrowsers()
    {
        $startDate = $this->now->modify('-1 year')->format('Y-m-d');
        $endDate = date('Y-m-d');

        $this->client
            ->shouldReceive('performQuery')
            ->with($this->siteId, $startDate, $endDate, "ga:sessions",
                array(
                    'dimensions' => 'ga:browser',
                    'sort' => '-ga:sessions'
                )
            )
            ->andReturn((object) array('rows' => array(array('Google Chrome', '123'))));

        $googleResult = $this->analytics->getTopBrowsers();

        $this->assertEquals($googleResult, array(array('browser' => 'Google Chrome', 'sessions' => '123')));
    }

    public function testGetMostVisitedPages()
    {
        $startDate = $this->now->modify('-1 year')->format('Y-m-d');
        $endDate = date('Y-m-d');

        $this->client
            ->shouldReceive('performQuery')
            ->with($this->siteId, $startDate, $endDate, "ga:pageviews",
                array(
                    'dimensions' => 'ga:pagePath',
                    'sort' => '-ga:pageviews',
                    'max-results' => 20
                )
            )
            ->andReturn((object) array('rows' => array(array('visited url', '123'))));

        $googleResult = $this->analytics->getMostVisitedPages();

        $this->assertEquals($googleResult, array(array('url' => 'visited url', 'pageViews' => '123')));
    }

    public function testGetSiteIdByUrl()
    {
        $testUrl = 'www.google.com';
        $siteId = 12345;

        $this->client->shouldReceive('getSiteIdByUrl')->with($testUrl)->andReturn($siteId);

        $result = $this->analytics->getSiteIdByUrl($testUrl);

        $this->assertEquals($result, $siteId);
    }

    public function testPerformQuery()
    {
        $startDate = $this->now->modify('-1 year');
        $endDate = new DateTime();
        $metrics = 'ga:somedummymetric';
        $others = array('first', 'second');

        $queryResult = 'result';

        $this->client
            ->shouldReceive('performQuery')
            ->with($this->siteId, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $metrics, $others)
            ->andReturn($queryResult);

        $googleResult = $this->analytics->performQuery($startDate, $endDate, $metrics, $others);

        $this->assertSame($googleResult, $queryResult);
    }

    public function testIsEnabled()
    {
        $enabledAnalytics = new Analytics($this->client, $this->siteId);
        $this->assertTrue($enabledAnalytics->isEnabled());

        $disabledAnalytics = new Analytics($this->client);
        $this->assertFalse($disabledAnalytics->isEnabled());
    }
    
    public function testPerformRealTimeQuery()
    {
        $metrics = 'rt:somedummymetric';
        $others = array('first', 'second');

        $queryResult = 'result';

        $this->client
            ->shouldReceive('performRealTimeQuery')
            ->with($this->siteId, $metrics, $others)
            ->andReturn($queryResult);

        $googleResult = $this->analytics->performRealTimeQuery($metrics, $others);

        $this->assertSame($googleResult, $queryResult);
    }

    public function testGetActiveUsers()
    {
        $others = array('first', 'second');
        $metrics = 'rt:activeUsers';

        $this->client
                ->shouldReceive('performRealTimeQuery')
                ->with($this->siteId, $metrics, $others)
                ->andReturn((object) array('rows' => array(array(0, '500'))));

            $googleResult = $this->analytics->getActiveUsers($others);

            $this->assertInternalType('int', $googleResult);
    }
}
