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

$FQ =   new \projectivemotion\EasyJetScraper\FlightQuery('SKG', 'BSL', '2017-05-16', '2017-05-20');
$Scraper    =   new \projectivemotion\EasyJetScraper\Scraper();

try{
    $flights    =   $Scraper->getFlights($FQ);
    print_r($flights);
}catch (\projectivemotion\EasyJetScraper\Exception $blocked)
{
    echo("Scraper blocked! Message: " . $blocked->getMessage(). "\n");
    exit(1);    // error
}


