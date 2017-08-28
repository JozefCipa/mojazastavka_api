<?php

namespace App\Library\CP;

class Line{

	public $from;
	public $to;
	public $departure;
	public $arrival;
	public $vehicle;
	public $walkDuration;
	public $delay;
	public $linkNumber;
	public $date;

	function __construct(){
	    $this->from = '';
	    $this->to = '';
	    $this->departure = '';
	    $this->arrival = '';
	    $this->vehicle = '';
	    $this->walkDuration = '';
	    $this->delay = '';
	    $this->linkNumber = '';
	    $this->date = '';
	}

   	function prepareObj($json = false){
        if($this->vehicle == 'Presun'){
            $result = [
                "from" 		   => trim($this->from),
                "to" 		   => trim($this->to),
                "walkDuration" => trim($this->walkDuration)
            ];
        }
        else{
        	$result = [
	            "vehicle" 		=> $this->vehicle == 'Električka' ? 'TRAM' : 'BUS',
	            "linkNumber" 	=> trim($this->linkNumber),
	            "from" 			=> trim($this->from),
	            "to" 			=> trim($this->to),
	            "departure" 	=> trim($this->departure),
	            "arrival" 		=> trim($this->arrival),
	            "delay" 		=> trim($this->delay)
	        ];
        }
        
        return $json ? json_encode($result) : $result;
    }
}