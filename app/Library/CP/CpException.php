<?php

namespace App\Library\CP;
use \Exception;

class CpException extends Exception{

	const TIME_FORMAT = 1;
	const STOPS_NOT_FOUND = 2;
	const START_STOP_EMPTY = 3;
	const DESTINATION_STOP_EMPTY = 4;
	const VEHICLE_EMPTY = 5;
	const CITY_EMPTY = 6;

	 public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
