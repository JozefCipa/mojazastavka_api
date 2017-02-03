<?php

use Illuminate\Http\Request;

$app->get('/', function () use ($app) {
    return view('app');
});

$app->get('/script', function () use ($app) {

	//used to parse data to DB from resources
    dispatch(new App\Jobs\CityJob);
});

$app->get('/find-stops', function (Request $request) use ($app) {


	$city_exists = (boolean) app('db')->select('SELECT 1 FROM `cities` WHERE `name` = ?', [$request->get('city')]);

	//check if city exists in our database
	if(! $city_exists)
		return err('Toto mesto žial nie je zatiaľ implementované');

	if(! $request->get('user_location') || 
		! $request->get('destination') || 
		! $request->get('user_location')['name'] ||
		! $request->get('destination')['name'])
			return err('Zlý formát dát');

	//count of stops to return
	$count = $request->get('count', 3);

	//if request doesn't contains geo coordinates for user_location, send request for them
	if(! $request->get('user_location')['lat'] || ! $request->get('user_location')['lng']){
		
		try{
			$user_location_geo = gapi_get_coords($request->get('user_location')['name']);
		}
		catch(Exception $e){
			return err($e->getMessage());
		}
	}
	else{
		$user_location_geo = [
			'lat' => $request->get('user_location')['lat'],
			'lng' => $request->get('user_location')['lng']
		];
	}

	// find nearest stations in user location
	$nearest_user_location_stops = find_stops_in_nearby($user_location_geo['lat'], $user_location_geo['lng'], $count);

	//if request doesn't contains geo coordinates for destination, send request for them
	if(! $request->get('destination')['lat'] || ! $request->get('destination')['lng']){

		try{
			$destination_geo = gapi_get_coords($request->get('destination')['name']);
		}
		catch(Exception $e){
			return err($e->getMessage());
		}
	}
	else{
		$destination_geo = [
			'lat' => $request->get('destination')['lat'],
			'lng' => $request->get('destination')['lng']
		];
	}

	// find nearest stations in user location
	$nearest_destination_stops = find_stops_in_nearby($destination_geo['lat'], $destination_geo['lng'], $count);

	return response()->json([

		'status' => 'OK',

		'current_location_name' => $request->get('user_location')['name'],
		'destination_name' => $request->get('destination')['name'],

		//searched place
		'destination' => $destination_geo,

		//stops near searched place
		'destination_stops' => $nearest_destination_stops,

	 	//stops near user current location
		'nearby_stops' => $nearest_user_location_stops
	]);
});

/*
|---------------------------------------------------------------------------
| Find nearest stops stored in DB according to given latitude and longitude
|---------------------------------------------------------------------------
*/
function find_stops_in_nearby($lat, $lng, $count){

	//Explanation of query: https://developers.google.com/maps/articles/phpsqlsearch_v3
	$results = app('db')->select('

		SELECT s.name, s.lat, s.lng, s.link_numbers, v.name as type,
		( 6371 * acos( cos( radians(?) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(?) ) + sin( radians(?) ) * sin( radians( lat ) ) ) ) 
		AS distance,
		FORMAT((SELECT distance) * 1000, 0) as distance_in_meters
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
| Sends request to Google maps to obtain geocde for given location
|-----------------------------------------------------------------
*/
function gapi_get_coords($location){

	$url = sprintf(env('GOOGLE_MAPS_GEOCODE_URL'), $location, env('GOOGLE_MAPS_API_KEY'));

    $client = new \GuzzleHttp\Client();
	$res = $client->request('GET', $url);
	
	if($res->getStatusCode() != 200)
		throw new Exception('Niečo sa pokazilo :/');

	$decoded_location = json_decode($res->getBody(), 1)['results'][0]['geometry']['location'];

	return $decoded_location;
}

/*
|-------------------------------
| Returns JSON error response
|-------------------------------
*/
function err($message){
	return response()->json([
		'status' => 'ERROR',
		'error' => $message
	]);
}