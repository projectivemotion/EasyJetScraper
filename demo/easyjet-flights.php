<?php
/**
 * Project: EasyJetScraper
 *
 * @author Amado Martinez <amado@projectivemotion.com>
 */

// Used for testing. Run from command line.
if(!isset($argv))
    die("Run from command line.");

// copied this from doctrine's bin/doctrine.php
$autoload_files = array( __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php');

foreach($autoload_files as $autoload_file)
{
    if(!file_exists($autoload_file)) continue;
    require_once $autoload_file;
}
// end autoloader finder

$FQ =   new \projectivemotion\EasyJetScraper\FlightQuery('SKG', 'SXF', '2016-05-01', '2016-05-02');
$Scraper    =   new \projectivemotion\EasyJetScraper\Scraper();
$Scraper->cacheOn();
// Uncomment for development purposes
//$Scraper->setCacheDir('../');
//$Scraper->cacheOff();
//$Scraper->verboseOn();

try{
    $flights    =   $Scraper->getFlights($FQ);
}catch (\projectivemotion\EasyJetScraper\ScraperBlockedException $blocked)
{
    die("Scraper blocked! Message: " . $blocked->getMessage(). "\n");
}

foreach(array('outbound', 'inbound') as $direction)
{
    foreach($flights->$direction as $date_YMD   =>  $flight)
    {
        if($flight->available)
            printf("%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $direction,
                "Available",
                $date_YMD,
                $flight->flight_number,
                $flight->flightDepartureDate->format("Y-m-d H:i:s"),
                $flight->flightArrivalDate->format("Y-m-d H:i:s"),
                $flight['charge-debit'],
                $flight['charge-debit-full'],
                $flight['charge-credit'],
                $flight['charge-credit-full']
            );
        else
            printf("%s,%s,%s\n", $direction, "Unvailable", $date_YMD);
    }
}