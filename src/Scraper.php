<?php
/**
 * Project: EasyJetScraper
 *
 * @author Amado Martinez <amado@projectivemotion.com>
 */

namespace projectivemotion\EasyJetScraper;


use projectivemotion\PhpScraperTools\CacheScraper;

class Scraper extends CacheScraper
{
    protected $domain   =   'www.easyjet.com';
    protected $cache_prefix =   'easyjet';

    protected $initialized      =   false;

    public static function QueryToPOST(FlightQuery $query)
    {
        return [
            'dep'   =>  $query->getOrigin(),
            'dest'  =>  $query->getDestination(),
            'dd'    =>  $query->getOutboundDateString(),
            'rd'    =>  $query->getInboundDateString(),
            'isOneWay'  =>  $query->IsOneWayFlight() ? 'on' : 'off',
            'searchFrom'    =>  'SearchPod|/en/',
            'pid'   =>  'www.easyjet.com',
            'apax'  =>  1,
            'cpax'  =>  0,
            'ipax'  =>  0,
            'lang'  =>  'EN'
        ];
    }

    public function getCookieFileName()
    {
        return 'easyjet-cookie.txt';
    }


    public function InitHome()
    {
        $this->setInitialized(true);
        return $this->cache_get('/en/');
    }

    public function cacheFilename($url, $post, $JSON)
    {
        return basename($url) . '.html';
    }

    public function getFlightNumber($flightToAddState, $pagevars, $isInbound)
    {
        $sendVars   =   $pagevars;
        $sendVars['flightToAddState']   =   $flightToAddState;

        $response   =   $this->getCurl('/EN/BasketView.mvc/AddFlight', $sendVars);
        
        $json   =   json_decode($response);

        if(!$json)
            throw new Exception("Unable to add Flight.");

        if(!preg_match_all('/Flight (\w+)/', $json->Html, $matches))
            throw new Exception('Unable to find Flight Number!');
        
        return $matches[1][(int)$isInbound];
    }

    public static function getBasketOptions($page)
    {
        if(!preg_match("#BasketOptions[^=]*=[\\s\\S]*?'(\w+)'#", $page, $matches))
            throw new Exception('Unable to find BasketOptions');

        return $matches[1];
    }

    public function getFlightsInfo($days_pq, $vars, $isInbound = 0)
    {
        $dates  =   [];

        /**
         * @var \phpQueryObject $day_el
         */
        foreach($days_pq as $day_el)
        {
            $day_div    =   pq($day_el);
            $date_attr  =   $day_div->attr('data-column-date');
            if(empty($date_attr))   continue;

            $date_YMD   =   date_create_from_format('dmY H:i:s', $date_attr . ' 00:00:00')->format('Y-m-d');
            $isUnavailable    =   $day_div->find('.unavailable')->length > 0;

            $date_info   = ['available'   =>  false];

            if(!$isUnavailable)
            {
                $flight_element =   $day_div->find('li > a');
                $date_info  =   [
                    'available'   =>  true,
                    'flightArrivalDate'   =>  date_create('@' . preg_replace('/\D/', '', $day_div->find('.flightArrivalDate')->val())/1000),
                    'flightDepartureDate'   =>  date_create('@' . preg_replace('/\D/', '', $day_div->find('.flightDate')->val())/1000),
                    'charge-debit'  =>  strip_tags($flight_element->attr('charge-debit')),
                    'charge-debit-full' =>  strip_tags($flight_element->attr('charge-debit-full')),
                    'charge-credit' =>  strip_tags($flight_element->attr('charge-credit')),
                    'charge-credit-full'    =>  strip_tags($flight_element->attr('charge-credit-full')),
                    'flight_number' =>  $this->getFlightNumber($day_div->find('li')->attr('id'), $vars, $isInbound)
                ];
            }

            $dates[$date_YMD]   =   new \ArrayObject($date_info, \ArrayObject::ARRAY_AS_PROPS);
        }

        return $dates;
    }

    public static function getPageVars(\phpQueryObject $doc)
    {
        return [
            '__BasketState' =>  $doc['#__BasketState']->val(),
            'flightSearchSession'   => $doc['#flightSearchSession']->val(),
            'flightToAddState'  =>  NULL,   // to be defined
            'flightOptionsState'    =>  'Visible',
            'basketOptions' =>  self::getBasketOptions($doc->html()),
        ];
    }

    public function getPageFlightsInfo($page)
    {
        $doc    =   \phpQuery::newDocument($page);
        $outbound_divs   =   $doc['#OutboundFlightDetails .OutboundDaySliderContainer .day'];
        $inbound_divs    =   $doc['#ReturnFlightDetails .ReturnDaySliderContainer .day'];
        $pagevars   =   self::getPageVars($doc);

        $info   =   (object)[];
        // the order of the following two lines is a bit of a hack and may need to be fixed in the near future.
        // if an inbound flight is selected before outbound flight, it will display that flight information on top of
        // the empty outbound flight info.
        $info->inbound =   $this->getFlightsInfo($inbound_divs, $pagevars, 0);
        $info->outbound =   $this->getFlightsInfo($outbound_divs, $pagevars, 0);

        return $info;
    }

    public function getFlights(FlightQuery $query)
    {
        // init some cookies and things
        if(!$this->getInitialized())
        {
            $home   =   $this->InitHome();
        }

        $searchParams   =   self::QueryToPOST($query);

        $page   =   $this->cache_get('/links.mvc?' . http_build_query($searchParams));

        $results    =   $this->getPageFlightsInfo($page);

        return $results;
    }

    public function setInitialized($initialized)
    {
        $this->initialized = $initialized;
    }

    public function getInitialized()
    {
        return $this->initialized;
    }
}