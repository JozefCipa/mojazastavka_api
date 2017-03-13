<?php

use Illuminate\Http\Request;
use App\Library\GoogleMapApi;
use App\Library\CP\CpSK;
use App\Library\CP\CpException;

$app->get('/', function (){
    return view('welcome');
});

// // uncomment, when importing new stops
// $app->get('/add-city-stops', function (){

// 	//used to parse data to DB from resources
//     dispatch(new App\Jobs\CityJob);
// });

$app->group(['middleware' => 'time', 'prefix' => '/api/v2'], function () use ($app) {
    
    //create GoogleMapApi instance for sending map requests
    $google = new GoogleMapApi([
    	'GOOGLE_MAPS_GEOCODE_URL' 		=> env('GOOGLE_MAPS_GEOCODE_URL'),
    	'GOOGLE_MAPS_API_KEY' 			=> env('GOOGLE_MAPS_API_KEY'),
    	'GOOGLE_MAPS_GEOLOCATE_LAT_LNG' => env('GOOGLE_MAPS_GEOLOCATE_LAT_LNG'),
    	'GOOGLE_MAPS_DIRECTIONS' 		=> env('GOOGLE_MAPS_DIRECTIONS')
    ]);

    //initialize cp.sk api
    $cpsk = new CpSK;

    //all cities for whose are stops available in DB
    $app->get('/cities', function (Request $request) use ($app){

    	try{
    		$cities = app('db')->select('SELECT * FROM `cities`');

			return response()->json([
				'status' => 'OK',
				'cities' => $cities
			]);

    	}catch(Exception $e){
    		return err($e);
    	}
	});

    //find nearest departures from given stop
    $app->get('/find/departures', function(Request $request) use ($cpsk){
    	
    	if(! validateDeparturesRequest($request))
			return err(new Exception('Zlý formát dát', env('ERR_CODE')));
    	
    	try{
    		$departures = $cpsk->from($request->get('start'))
	    		->to($request->get('destination'))
	    		->useVehicles(CpSK::MHD)
	    		->inCity($request->get('city'))
	    		->at($request->get('time'))
	    		->find();

	    	return response()->json([
	    		'status' => 'OK',
	    		'departuresParams' => $request->all(),
	    		'departures' => $departures
	    	]);
	    }
	    catch(Exception $e){
	    	return err($e);
	    }
    });

    //main API endpoint, finds stops
	$app->get('/find/stops', function (Request $request) use ($google) {

		if(! validateStopsRequest($request))
			return err(new Exception('Zlý formát dát', env('ERR_CODE')));
		
		//count of stops to return
		$count = $request->get('count', 3);

		//city
		$city = $request->get('city');
		if($city){
			$city = ', ' . $city;
		}

		//if exists user location name, geolocate it, else use geo coordinates
		if(array_key_exists('name', $request->get('start')) && trim($request->get('start')['name'])){
			try{
				$startLocationGeo = $google->geolocateAddress($request->get('start')['name'] . $city);
			}
			catch(Exception $e){
				return err($e);
			}
		}
		else{
			$startLocationGeo = [
				'latitude' => (float) $request->get('start')['lat'],
				'longitude' => (float) $request->get('start')['lng']
			];
		}

		// find nearest stations in user location
		$nearbyStops = $google->findStopsInNearby($startLocationGeo['latitude'], $startLocationGeo['longitude'], $count);

		//if exists destination location name, geolocate it, else use geo coordinates
		if(array_key_exists('name', $request->get('destination')) && trim($request->get('destination')['name'])){

			try{
				$destinationLocationGeo = $google->geolocateAddress($request->get('destination')['name'] . $city);
			}
			catch(Exception $e){
				return err($e);
			}
		}
		else{

			$destinationLocationGeo = [
				'latitude' => (float) $request->get('destination')['lat'],
				'longitude' => (float) $request->get('destination')['lng']
			];

			try{
				$destinationName = $google->geolocateLatLng($destinationLocationGeo);
			}
			catch(Exception $e){
				return err($e);
			}
		}

		// find nearest stations in user location
		$destinationStops = $google->findStopsInNearby($destinationLocationGeo['latitude'], $destinationLocationGeo['longitude'], $count);

		//find directions for every stop
		if($request->get('directions') !== 'false' /* && false */){
			foreach ($nearbyStops as $stop) {
				$stop->directions = $google->findDirections((array) $stop, $startLocationGeo);
			}

			foreach ($destinationStops as $stop) {
				$stop->directions = $google->findDirections((array) $stop, $destinationLocationGeo);
			}
		}

		return response()->json([

			//response info
			'status' => 'OK',

			//base data
			'city' => $city,
			'startName' => array_key_exists('name', $request->get('start')) ? $request->get('start')['name'] : '',
			'destinationName' => array_key_exists('name', $request->get('destination')) && !empty($request->get('destination')['name']) ? $request->get('destination')['name']: $destinationName,

			//searched place geo
			'destinationGeo' => $destinationLocationGeo,

			//starting location geo
			'startGeo' => $startLocationGeo,

			//stops near searched place
			'destinationStops' => $destinationStops,

		 	//stops near user current location
			'nearbyStops' => $nearbyStops
		]);
	});
});

/*
|-----------------------------------------------
| Checks if required values are sent in request
|-----------------------------------------------
*/
function validateStopsRequest(Request $request){

	try{
		$requiredArrays = $request->get('start') && $request->get('destination');

		//if exists either name or lat and lng params
		$userLocationArray = array_key_exists('name', $request->get('start')) || 
			( array_key_exists('lat', $request->get('start')) && array_key_exists('lng', $request->get('start') )
		);

		$destinationLocationArray = array_key_exists('name', $request->get('destination')) || 
			( array_key_exists('lat', $request->get('destination')) && array_key_exists('lng', $request->get('destination') )
		);

		return $requiredArrays && $userLocationArray && $destinationLocationArray;
	}catch(Exception $e){
		return false;
	}
}

function validateDeparturesRequest(Request $request){

	try{

		// start stop, destination, city, 

		if(trim($request->get('start')) == '' ||
			trim($request->get('destination')) == '' ||
			trim($request->get('city')) == ''){
			return false;
		}

		return true;

	}catch(Exception $e){
		return false;
	}

}

/*
|-------------------------------
| Returns JSON error response
|-------------------------------
*/
function err(Exception $e){

	// check if error message from exception can be showed to user
	// if exception is catched in constructor is ERR_CODE
	// or code just crashed
	if($e->getCode() == env('ERR_CODE') || $e instanceof CpException)
		$message = $e->getMessage();
	else{
		$message = 'Nastala chyba.\nNa jej odstránení sa pracuje.';

		//TODO: send me an email
	}

	return response()->json([
		'status' => 'ERROR',
		'error' => $message
	]);
}