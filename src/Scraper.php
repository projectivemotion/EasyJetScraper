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
    protected $delay    =   10;

    protected $adults   =   1;
    protected $children =   0;
    protected $infants  =   0;

    public function setDelay($delay)
    {
        $this->delay = $delay;
    }

    public function setAdults($adults)
    {
        $this->adults = $adults;
    }

    public function setChildren($children)
    {
        $this->children = $children;
    }

    public function setInfants($infants)
    {
        $this->infants = $infants;
    }

    public function getAdults()
    {
        return $this->adults;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getInfants()
    {
        return $this->infants;
    }
    
    public function delay()
    {
        if(!$this->delay)   return;
        sleep($this->delay);
    }

    public function QueryToPOST(FlightQuery $query)
    {
        if($query->getOutboundDate() < date_create())
            throw new Exception("Please check your flight dates: " . $query->getOutboundDateString());

        return [
            'dep'   =>  $query->getOrigin(),
            'dest'  =>  $query->getDestination(),
            'dd'    =>  $query->getOutboundDateString(),
            'rd'    =>  $query->getInboundDateString(),
            'isOneWay'  =>  $query->IsOneWayFlight() ? 'on' : 'off',
            'searchFrom'    =>  'SearchPod|/en/',
            'pid'   =>  'www.easyjet.com',
            'apax'  =>  $this->getAdults(),
            'cpax'  =>  $this->getChildren(),
            'ipax'  =>  $this->getInfants(),
            'lang'  =>  'EN'
        ];
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
        $this->delay();

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

    public function throwException($page, $message)
    {
        $error_page =   'easyjet-' . date('Ymd') . '.html';

        if($this->use_cache)
        {
            $path   =   $this->getCacheDir() . $error_page;
            file_put_contents($path, $page);
            $message .= "\nPlease look in $path";
        }

        if(stripos($page, 'blocked@easyjet.com') !== FALSE)
        {
            throw new ScraperBlockedException($message);
        }

        throw new Exception($message);
    }

    public function getBasketOptions($page)
    {
        if(!preg_match("#BasketOptions[^=]*=[\\s\\S]*?'(\w+)'#", $page, $matches))
            $this->throwException($page, 'Unable to find BasketOptions');

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

    public function getPageVars(\phpQueryObject $doc)
    {
        return [
            '__BasketState' =>  $doc['#__BasketState']->val(),
            'flightSearchSession'   => $doc['#flightSearchSession']->val(),
            'flightToAddState'  =>  NULL,   // to be defined
            'flightOptionsState'    =>  'Visible',
            'basketOptions' =>  $this->getBasketOptions($doc->html()),
        ];
    }

    public function getPageFlightsInfo($page)
    {
        $doc    =   \phpQuery::newDocument($page);
        $outbound_divs   =   $doc['#OutboundFlightDetails .OutboundDaySliderContainer .day'];
        $inbound_divs    =   $doc['#ReturnFlightDetails .ReturnDaySliderContainer .day'];
        $pagevars   =   $this->getPageVars($doc);

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
        $searchParams   =   $this->QueryToPOST($query);

        // init some cookies and things
        if(!$this->getInitialized())
        {
            $home   =   $this->InitHome();
            $this->delay();
        }

        $page   =   $this->cache_get('/links.mvc?' . http_build_query($searchParams));

        $this->delay();

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

    public function removeAllCookies()
    {
        unlink($this->getCookieFileName());
    }
}