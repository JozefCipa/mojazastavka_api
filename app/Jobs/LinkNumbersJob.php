<?php

namespace App\Jobs;
use \GuzzleHttp\Client;

class LinkNumbersJob extends Job
{

    protected $city;
    protected $cpsk_idoskey;

    const LINK_NUMBERS_URL = 'http://cp.atlas.sk/AJAXService.asmx/SearchTimetableObjects';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($city, $cpsk_idoskey)
    {
        $this->city = $city;
        $this->cpsk_idoskey = $cpsk_idoskey;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $curl = curl_init();

        $stops = collect(app('db')->select('
            SELECT `stops`.`name` 
            FROM `stops`
            JOIN `cities` ON `stops`.`city_id` = `cities`.`id`
            WHERE `cities`.`name_escaped` = ?', [$this->city]))->pluck('name');

        $currentCity = collect(app('db')->select('SELECT `id`, `cpsk_selectedTT` FROM `cities` WHERE `name_escaped` = ?', [$this->city]))->first();

        foreach ($stops as $stopName) {

            curl_setopt_array($curl, [
                CURLOPT_URL => self::LINK_NUMBERS_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode([
                    'timestamp' => time(),
                    'prefixText' => $stopName,
                    'count' => 20,
                    'selectedTT' => $currentCity->cpsk_selectedTT,
                    'bindElementValue' => '',
                    'bindElementValue2' => '',
                    'iLang' => 'SLOVAK',
                    'bCoor' => false
                ]),
                CURLOPT_HTTPHEADER => [
                    'content-type: application/json',
                    "idoskey: {$this->cpsk_idoskey}"
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            if ($err) {
              throw new Exception($err);
            }
            else{

                $decodedRes = json_decode($response, true);

                if($decodedRes['d'] == null){
                    echo '[ERR]: Not found for stop: ' . $stopName . '<br>';
                }
                else{
                    $linkNumbers = $decodedRes['d'][0]['sLines'];

                    app('db')->update('UPDATE `stops` SET `link_numbers` = ? WHERE `name` = ? AND `city_id` = ?', [
                        $linkNumbers,
                        $stopName,
                        $currentCity->id
                    ]);
                }
            }
        }

        curl_close($curl);
        echo 'Done.';
    }
}
