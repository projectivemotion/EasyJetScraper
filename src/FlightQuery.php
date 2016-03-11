<?php
/**
 * Project: EasyJetScraper
 *
 * @author Amado Martinez <amado@projectivemotion.com>
 */

namespace projectivemotion\EasyJetScraper;


class FlightQuery
{
    protected $origin;
    protected $destination;

    protected $dateFormatString =   'Y-m-d';

    /**
     * @var \DateTime
     */
    protected $outbound_date;

    /**
     * @var \DateTime
     */
    protected $inbound_date;

    public function setDateFormatString($dateFormatString)
    {
        $this->dateFormatString = $dateFormatString;
    }

    public function getDateFormatString()
    {
        return $this->dateFormatString;
    }

    public function setOutboundDate($outbound_date)
    {
        $this->outbound_date = \DateTime::createFromFormat('Y-m-d H:i:s', $outbound_date . ' 00:00:00'); //$departure_date;
//        $this->departure_date = Carbon::createFromFormat('Y-m-d H:i:s', $departure_date . ' 00:00:00'); //$departure_date;
    }

    public function setInboundDate($inbound_date)
    {
        $this->inbound_date = \DateTime::createFromFormat('Y-m-d H:i:s', $inbound_date. ' 00:00:00');
//        $this->return_date = Carbon::createFromFormat('Y-m-d H:i:s', $return_date. ' 00:00:00');
    }

    public function setOrigin($origin)
    {
        $this->origin = $origin;
    }

    public function setDestination($destination)
    {
        $this->destination = $destination;
    }

    public function getOutboundDateString($format = '')
    {
        return $this->outbound_date->format($format ?: $this->getDateFormatString());
    }

    public function getInboundDateString($format = '')
    {
        return $this->inbound_date->format($format ?: $this->getDateFormatString());
    }

    public function getInboundDate()
    {
        return $this->inbound_date;
    }

    public function getOutboundDate()
    {
        return $this->outbound_date;
    }

    public function getOrigin()
    {
        return $this->origin;
    }

    public function getDestination()
    {
        return $this->destination;
    }

    public function IsReturnFlight()
    {
        return !empty($this->inbound_date);
    }

    public function IsOneWayFlight()
    {
        return empty($this->inbound_date);
    }

    public function __construct($origin = '', $destination = '', $departure_date = '', $return_date = '')
    {
        $this->setOrigin($origin);
        $this->setDestination($destination);
        $this->setOutboundDate($departure_date);
        $this->setInboundDate($return_date);
    }
}