<?php
/**
 * Project: EasyJetScraper
 *
 * @author Amado Martinez <amado@projectivemotion.com>
 */

namespace projectivemotion\EasyJetScraper\Tests;


use projectivemotion\EasyJetScraper\FlightQuery;
use projectivemotion\EasyJetScraper\Scraper;

class ScraperTest extends \PHPUnit_Framework_TestCase
{
    public function testFoo()
    {
        $scraper = new Scraper();
        $fq = new FlightQuery('SKG', 'BSL', '2017-05-16', '2017-05-20');
        try {
            $response = $scraper->getFlights($fq);
            $this->assertInternalType('object', $response);
        }catch(\Exception $e)
        {
            $this->assertTrue(FALSE, $e->getMessage());
        }
    }
}
