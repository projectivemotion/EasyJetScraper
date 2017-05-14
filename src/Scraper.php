<?php
/**
 * Project: EasyJetScraper
 *
 * @author Amado Martinez <amado@projectivemotion.com>
 */

namespace projectivemotion\EasyJetScraper;


use GuzzleHttp\Client;

class Scraper
{
    /**
     * @var Client
     */
    protected $client;

    protected $options  =   [];

    public function __construct($options = [])
    {
        $this->options  =   ['cookies' => true, 'base_uri'   =>  'https://www.easyjet.com/'] + $options;
    }

    public function setClient($client)
    {
        $this->client = $client;
    }

    public function getClient()
    {
        if(!$this->client){
            $this->setClient(new Client($this->options));
        }
        return $this->client;
    }

    public function getRequestUrl(FlightQuery $query)
    {
        $int1d = \DateInterval::createFromDateString('P1D');
        $params = ['AdditionalSeats' => '0',
            'AdultSeats' => '2',
            'ArrivalIata' => $query->getDestination(),
            'ChildSeats' => '0',
            'DepartureIata' => $query->getOrigin(),
            'IncludeAdminFees' => 'true',
            'IncludeFlexiFares' => 'false',
            'IncludeLowestFareSeats' => 'true',
            'IncludePrices' => 'true',
            'IsTransfer' => 'false',
            'LanguageCode' => 'EN',
            'MaxDepartureDate' => $query->getOutboundDate()->add($int1d)->format('Y-m-d'),
            'MaxReturnDate' => $query->getInboundDate()->add($int1d)->format('Y-m-d'),
            'MinDepartureDate' => $query->getOutboundDate() ->sub($int1d)->format('Y-m-d'),
            'MinReturnDate' => $query->getInboundDate() ->sub($int1d)->format('Y-m-d') ];

        $url = "/ejavailability/api/v9/availability/query?" . http_build_query($params);

        return $url;
    }

    public function getFlights(FlightQuery $query)
    {
        $response = $this->getClient()->get(
            $this->getRequestUrl($query)
        );

        $statusCode = $response->getStatusCode();

        if($statusCode !== 200) {
            throw new Exception("Response Code " . $statusCode);
        }

        $jsonstring = (string)$response->getBody();

        return \GuzzleHttp\json_decode($jsonstring);
    }
}