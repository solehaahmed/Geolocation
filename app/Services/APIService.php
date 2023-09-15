<?php

namespace App\Services;
 
use Config;

class APIService
{
    private $apiUrl;

    private $key;

    /*
        Constructor for file
    */
    public function __construct() {

        $this->apiUrl = Config::get('services.geolocation.url');
        $this->key = Config::get('services.geolocation.key');

    }
    
    /*
        Make Api Request
    */
    public function makeAPIRequest($queryString)
    {
        $queryString = $this->buildHttpQuery($queryString);
        
        $ch = curl_init(sprintf('%s?%s', $this->apiUrl, $queryString));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $json = curl_exec($ch);

        curl_close($ch);

        $apiResult = json_decode($json, true);

        return $apiResult;            
    }

    /*
        Build Http query
    */
    public function buildHttpQuery(array $data) 
    {   
        $data['access_key'] = $this->key;
        $data['output'] = 'json';
        $data['limit'] = 1;
        $data['fields '] = 'data.latitude,data.longitude';

        return http_build_query($data);
    }
}