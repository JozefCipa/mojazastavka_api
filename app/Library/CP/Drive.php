<?php

namespace App\Library\CP;

class Drive{

	public $duration;
	public $distance;

	public $lines;

	function __construct(){
    	$this->duration = null;
    	$this->distance = null;

    	$this->lines = [];
    }

    function prepareObj($json = false){

        if($json){
            return $result = [
                "lines"     => array_map(function($line){return $line->json();}, $this->lines),
                "duration"  => trim($this->duration),
                "distance"  => trim($this->distance)
            ];
        }
        else{
            return  $result = [
                "lines"     => $this->lines,
                "duration"  => trim($this->duration),
                "distance"  => trim($this->distance)
            ];
        }
    }
}