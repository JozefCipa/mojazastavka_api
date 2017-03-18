<?php

namespace App\Library\CP;
use GuzzleHttp\Client;
use \DateTime;
use \DomDocument;
use \DomXPath;

use App\Library\CP\Line;
use App\Library\CP\Drive;
use App\Library\CP\CpException;

class CpSK{

	const BUS  = 'bus';
	const VLAK = 'vlak';
	const MHD  = 'mhd';

	private $httpClient;
	const URL = 'http://cp.atlas.sk/%s/spojenie/';

	private $start;
	private $destination;
	private $vehicle;

	private $timeDate;
	private $time;
	private $date;

	public function __construct(){

		$this->httpClient = new Client;
	}

	public function from($startStop){
		
		$this->start = $startStop;
		return $this;
	}

	public function to($destinationStop){

		$this->destination = $destinationStop;
		return $this;
	}

	public function useVehicles(){

		$vehicles = func_get_args();

		if(in_array(self::VLAK, $vehicles)){
			$this->vehicle .= self::VLAK;
		}

		if(in_array(self::BUS, $vehicles)){
			$this->vehicle .= self::BUS;
		}

		if(in_array(self::MHD, $vehicles)){
			$this->vehicle .= self::MHD;
		}

		return $this;
	}

	public function inCity($cityName){

		$this->vehicle = mb_strtolower(str_replace(' ', '', iconv('utf-8', 'us-ascii//TRANSLIT', $cityName)));

		return $this;
	}

	public function at($time){

		//validate time
		if($time == ''){
			return $this;
		}

		if((DateTime::createFromFormat('H:i d.m.Y', $time) !== false)) {
			$this->timeDate = $time;
		}
		else{
			throw new CpException('Wrong date format. Time should be in "HH:MM d.m.Y" format.', CpException::TIME_FORMAT);
		}

		return $this;
	}

	public function find(){

		$this->validate();

		if(! $this->timeDate){
			$this->time = date('H:i');
			$this->date = date('d.m.Y');
		}else{
			$tmp = explode(' ', $this->timeDate);
			$this->time = $tmp[0];
			$this->date = $tmp[1];
		}

		$res = $this->httpClient->request('GET', sprintf(self::URL, $this->vehicle), [
			'query' => [
				'date'	 => $this->date,
				'time'	 => $this->time,
				'f'		 => $this->start,
				't'		 => $this->destination,
				'submit' => 'true'
			]
		]);

		$parsed = $this->parseStopsFromHtml($res->getBody()->getContents());

		return $parsed;
	}

	private function parseStopsFromHtml($html){

		$routes = [];
		$htmlDom = new DomDocument();
		@$htmlDom->loadHTML($html);
		$xpath = new DOMXPath($htmlDom);
    	    	
    	//find tables with results
    	$tables = $xpath->query('//div[@id="main-res-inner"]/table/tbody');

    	if($tables->length == 0){
    		throw new CpException('Stops not found', CpException::STOPS_NOT_FOUND);
    	}

    	foreach ($tables as $table){
    		$drive = new Drive;

    		$tableDom = new DomDocument;
	        $tableDom->appendChild($tableDom->importNode($table, true));
	        $tableXpath = new DOMXPath($tableDom);

	        $dataLength = $tableXpath->query('./tr')->length;

	        for($i = 1; $i < $dataLength - 1; $i++){
	            $line = new Line;

	            $rowFrom = "./tr[$i]";
	            $rowTo = "./tr[" . ($i + 1) . "]";

	            $line->from = $this->_getLeafElement($tableXpath, $rowFrom . '/td[3]');
	            $line->to = $this->_getLeafElement($tableXpath, $rowTo . '/td[3]');
	            $line->departure = $this->_getLeafElement($tableXpath, $rowFrom . '/td[5]');
	            $line->arrival = $this->_getLeafElement($tableXpath, $rowTo . '/td[4]');
	            $line->vehicle = str_replace('Autobus', 'BUS', $tableXpath->query($rowFrom . '/td[7]/img[1]')[0]->getAttribute('title'));
	            
	            if($line->vehicle == 'Presun'){
	                $line->walkDuration = str_replace('Presun asi ', '', $tableXpath->query($rowFrom . '/td[7]/text()')[0]->nodeValue);
	            }

	            $delay = $tableXpath->query($rowFrom . '/td[7]/div[1]/span[@class!="nodelay"]/text()');

	            if($delay && $delay[0] != 'Aktuálne bez meškania'){
	                $line->delay = $delay[0];
	            }

	            $linkNumber = $tableXpath->query($rowFrom . '/td[last()]')[0]->nodeValue;

	            if($linkNumber){
	                $line->linkNumber = $linkNumber;
	            }

	            $date = $tableXpath->query($rowFrom . '/td[2]/text()')[0]->nodeValue;
	            if($date != ' '){
	                $line->date = $date;
	            }

	            $drive->lines[] = $line->prepareObj();
	        }

	        $drive->duration = $tableXpath->query("./tr[$dataLength]/td[3]/p/strong[1]/text()")[0]->nodeValue;

	        try{
	            $drive->distance = $tableXpath->query("./tr[$dataLength]/td[3]/p/strong[2]/text()")[0]->nodeValue;

	            if (strpos($drive->distance, 'EUR') !== false){
	                $drive->distance = '';
	            }
	        }
	        catch(OutOfBoundsException $e){
	            $drive->distance = 'Distance unknown';
	        }

	        $routes[] = $drive->prepareObj();
    	}

		return $routes;
	}

	/**
	*	Returns leaf element's text in given path
	*/
	private function _getLeafElement($xpath, $path){
        $res = $xpath->query($path . '/*[not(*)]');
        
        if($res->length){
            return $res[0]->nodeValue;
        }

        return $xpath->query($path . '/text()')[0]->nodeValue;
    }

	private function validate(){
		if (! trim($this->start)) {
			throw new CpException('You have to choose start stop.', CpException::START_STOP_EMPTY);
		}

		if (! trim($this->destination)) {
			throw new CpException('You have to choose destination stop.', CpException::DESTINATION_STOP_EMPTY);
		}

		if (! trim($this->vehicle)) {
			throw new CpException('You have to choose type of vehicle.', CpException::VEHICLE_EMPTY);
		}
		else if(trim($this->vehicle) === 'mhd') {
			throw new CpException('You have to choose city.', CpException::CITY_EMPTY);
		}
	}
}