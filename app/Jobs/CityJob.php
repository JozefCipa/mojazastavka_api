<?php

namespace App\Jobs;

class CityJob extends Job
{

    const CURRENT_CITY = 'ZA';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
         
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $urls = [
            'PO' => 'http://www.oma.sk/api?region=-2320257&tabulka=poi&typ=zastavka,stanica&limit=2000',
            'KE' => 'http://www.oma.sk/api?region=-1690324&tabulka=poi&typ=zastavka,zastavka-elektricky,stanica&limit=2000',
            'BA' => 'http://www.oma.sk/api?region=-1702499&tabulka=poi&typ=zastavka,zastavka-elektricky,stanica&limit=2000',
            'MI' => 'http://www.oma.sk/api?region=-2386541&tabulka=poi&typ=zastavka,stanica&limit=2000',
            'TN' => 'http://www.oma.sk/api?region=-1799254&tabulka=poi&typ=zastavka,stanica&limit=2000',
            'BB' => 'http://www.oma.sk/api?region=-2265296&tabulka=poi&typ=zastavka,stanica&limit=2000',
            'KO' => 'http://www.oma.sk/api?region=-2218900&tabulka=poi&typ=zastavka,stanica&limit=2000',
            'LM' => 'http://www.oma.sk/api?region=-2169318&tabulka=poi&typ=zastavka,stanica&limit=2000',
            'PP' => 'http://www.oma.sk/api?region=-2275137&tabulka=poi&typ=zastavka,stanica&limit=2000',
            'ZA' => 'http://www.oma.sk/api?region=-1859537&tabulka=poi&typ=zastavka,stanica&limit=2000'
        ];

        $vehicles = [
            'bus' => 1,
            'trolleybus' => 2,
            'tram' => 3
        ];

        $city_ids = [
            'PO' => 1,
            'KE' => 2,
            'BA' => 3,
            'MI' => 4,
            'TN' => 5,
            'BB' => 6,
            'KO' => 7,
            'LM' => 8,
            'PP' => 9,
            'ZA' => 10
        ];

        //load data
        $res = (new \GuzzleHttp\Client())->request('GET', $urls[self::CURRENT_CITY]);
        if($res->getStatusCode() != 200)
            throw new Exception('Niečo sa pokazilo :/');
        $responseBody = $res->getBody();

        //prepare response before parsing
        $responseBody = substr($responseBody, 181);
        $responseBody = substr($responseBody, 0, -1);

        //parse response to json
        $stops = json_decode($responseBody);

        foreach($stops as $stop){

            //find vehicle by name
            if(in_array('stanica', $stop->properties->typy))
                $vehicleTypeId = 1;
            elseif(in_array('zastavka', $stop->properties->typy))
                $vehicleTypeId = 1;
            elseif(in_array('something else', $stop->properties->typy))
                $vehicleTypeId = 2;
            elseif((in_array('zastavka-elektricky', $stop->properties->typy)))
                $vehicleTypeId = 3;

            app('db')->insert('INSERT INTO stops (name, lat, lng, city_id, vehicle_type_id) VALUES(?, ?, ?, ?, ?)', [
                $stop->properties->name,
                (float) $stop->geometry->coordinates[1],
                (float) $stop->geometry->coordinates[0],
                $city_ids[self::CURRENT_CITY],
                $vehicleTypeId
            ]);
        }

    }
}
