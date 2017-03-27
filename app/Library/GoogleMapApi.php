<?php

namespace App\Library;

class GoogleMapApi{


	private $httpClient;
	private $credentials;

	public function __construct(array $credentialsConfig){

		$this->httpClient = new \GuzzleHttp\Client;
		$this->credentials = $credentialsConfig;
	}

	/*
	|---------------------------------------------------------------------------
	| Find nearest stops stored in DB according to given latitude and longitude
	|---------------------------------------------------------------------------
	*/
	public function findStopsInNearby($lat, $lng, $count){

		//Explanation of query: https://developers.google.com/maps/articles/phpsqlsearch_v3
		$results = app('db')->select('
			SELECT s.id, s.name, s.lat as latitude, s.lng as longitude, s.link_numbers, v.name as type,
			( 6371 * acos( cos( radians(?) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(?) ) + sin( radians(?) ) * sin( radians( lat ) ) ) ) 
			AS distance,
			REPLACE(FORMAT((SELECT distance) * 1000, 0), ",", "") as distance_in_meters
			FROM stops s
			JOIN vehicle_types v ON v.id = s.vehicle_type_id
			HAVING distance < 5 ORDER BY distance LIMIT 0, ?',
			[ 
				(float) $lat, 
				(float) $lng,
				(float) $lat,
				$count
			]);

		return $results;
	}

	/*
	|-----------------------------------------------------------------
	| Sends request to Google maps to obtain geocde for given address
	|-----------------------------------------------------------------
	*/
	public function geolocateAddress($address){

		$url = sprintf($this->credentials['GOOGLE_MAPS_GEOCODE_URL'], $address, $this->credentials['GOOGLE_MAPS_API_KEY']);

		try{
			$res = $this->httpClient->request('GET', $url);
		}catch(\Exception $e){
			throw new \Exception('Nepodarilo sa nadviazať spojenie s Google serverom.', env('ERR_CODE'));
		}
		
		if($res->getStatusCode() != 200){
			throw new \Exception('Nepodarilo sa nadviazať spojenie s Google serverom.', env('ERR_CODE'));
		}

		$decodedRes = json_decode($res->getBody(), 1);

		if($decodedRes['status'] == 'ZERO_RESULTS')
			throw new \Exception("Nepodarilo sa nájsť '$address'", env('ERR_CODE'));

		$decoded_location = $decodedRes['results'][0]['geometry']['location'];

		return [
			'latitude' => (float) $decoded_location['lat'],
			'longitude' => (float) $decoded_location['lng']
		];
	}

	/*
	|------------------------------------------------------------------------------------
	| Sends request to Google maps to obtain location name according to given geo coords
	|-------------------------------------------------------------------------------------
	*/
	function geolocateLatLng(array $coords){
		
		$url = sprintf($this->credentials['GOOGLE_MAPS_GEOLOCATE_LAT_LNG'], $coords['latitude'], $coords['longitude'], $this->credentials['GOOGLE_MAPS_API_KEY']);

		try{
			$res = $this->httpClient->request('GET', $url);
		}catch(\Exception $e){
			throw new \Exception('Nepodarilo sa nadviazať spojenie s Google serverom.', env('ERR_CODE'));
		}
		
		if($res->getStatusCode() != 200){
			throw new \Exception('Nepodarilo sa nadviazať spojenie s Google serverom.', env('ERR_CODE'));
		}

		$name = json_decode($res->getBody(), 1)['results'][0]['formatted_address'];

		return $name;
	}
    
    /*
	|------------------------------------------------------------------------------------
	| Finds direction coords between start and destination location
	|-------------------------------------------------------------------------------------
	*/
	function findDirections(array $start, array $destination){

		$url = sprintf($this->credentials['GOOGLE_MAPS_DIRECTIONS'], $start['latitude'], $start['longitude'], $destination['latitude'], $destination['longitude'], $this->credentials['GOOGLE_MAPS_API_KEY']);

		try{
			$res = $this->httpClient->request('GET', $url);
		}catch(\Exception $e){
			throw new \Exception('Nepodarilo sa nadviazať spojenie s Google serverom.', env('ERR_CODE'));
		}
		
		if($res->getStatusCode() != 200){
			throw new \Exception('Nepodarilo sa nadviazať spojenie s Google serverom.', env('ERR_CODE'));
		}

		$directions = $this->decodeDirections(json_decode($res->getBody(), 1)['routes'][0]['overview_polyline']['points']);


		return $directions;
	}

	//https://github.com/emcconville/google-map-polyline-encoding-tool/blob/master/src/Polyline.php
	private function decodeDirections($string){
		$points = array();
        $index = $i = 0;
        $previous = array(0,0);
        while ($i < strlen($string)) {
            $shift = $result = 0x00;
            do {
                $bit = ord(substr($string, $i++)) - 63;
                $result |= ($bit & 0x1f) << $shift;
                $shift += 5;
            } while ($bit >= 0x20);
            $diff = ($result & 1) ? ~($result >> 1) : ($result >> 1);
            $number = $previous[$index % 2] + $diff;
            $previous[$index % 2] = $number;
            $index++;
            $points[] = $number * 1 / pow(10, 5); // 5 is precision
        }

        $latLngPoints = [];
        foreach(array_chunk($points, 2) as $pair){
        	$latLngPoints[] = [
        		'latitude' => $pair[0],
        		'longitude' => $pair[1]
        	];
        }

        return $latLngPoints;
	}
}
