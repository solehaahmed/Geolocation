<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\APIService;
use Config;
use App\Exceptions;
use File;

class CalculateLocationController extends Controller
{

    private $apiService;

    private $baseLocation;

    private $locationDistance = [];

    private $invalidAddress = [];

    /*
        Constructor for file
    */
    public function __construct(APIService $apiService)
    {
        $this->apiService = $apiService;
    }

    /*
        takes all the location and calculates thier longitudes and latitudes and sort and displays it.
    */
    public function index() : void
    {
        $locations = Config::get('location.locations');
       
        foreach ($locations as $key => $value) {
            $location = explode('-', $value);
           
            $queryString = ['query' => $location[1]];
         
            try {
                 $response = $this->apiService->makeAPIRequest($queryString);
            } catch(Exceptions $e) {
                echo $e->error; 
            }
            
            if (!$response) {
                $this->wrongAddress[$location[0]] = "Invalid Address";
                continue;  
            }

            $this->saveDistance($location, $response);        
        }

        //Sort the result 
        usort($this->locationDistance, fn($a, $b) => $a[1] <=> $b[1]);
        
        echo '<pre>'; print_r($this->locationDistance); echo '</pre>';

        echo '<pre>'; print_r($this->invalidAddress); echo '</pre>';
       
        $this->setCSV();
    }

    /*
        This calculates the latitude and longitude
    */
    public function saveDistance($location, $response) : void
    {
        if(trim($location[0]) == "Adchieve HQ") {
                $this->baseLocation = $response['data'][0];
                
        } else {

            $locationDistance = $response['data'][0]; 
    
            $this->distance($this->baseLocation['latitude'], $this->baseLocation['longitude'], 
                $locationDistance['latitude'], $locationDistance['longitude'], $location);
        }
    }

    /*
        Calculates the distance between two points.
    */
    public function distance($lat1, $lon1, $lat2, $lon2, $address) : void { 
        $pi80 = M_PI / 180; 
        $lat1 *= $pi80; 
        $lon1 *= $pi80; 
        $lat2 *= $pi80; 
        $lon2 *= $pi80; 
        $r = 6372.797; // radius of Earth in km 6371
        $dlat = $lat2 - $lat1; 
        $dlon = $lon2 - $lon1; 
        $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2); 
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a)); 
        $km = $r * $c; 
        $km = round($km,2);

        //Saves the details of location
        $this->locationDistance[] = [
            $address[0],
            $km, 
            $address[1]
        ]; 
    }

    /*
        This sets the CSV file for calculated distance
    */
    public function setCSV() {
        
        if (!File::exists(storage_path()."/files")) {
            File::makeDirectory(storage_path() . "/files");
        }

        $filename =  storage_path("files/download.csv");
        $handle = fopen($filename, 'w');

        fputcsv($handle, [
            "SortNumber",
            "Distance",
            "Name",
            "Address"
        ]);

        foreach ($this->locationDistance as $key => $value) {
            fputcsv($handle, [
                ++$key,
                $value[1],
                $value[0],
                $value[2]
            ]);
        }

        fclose($handle);       
        response()->download($filename);
    }
}
