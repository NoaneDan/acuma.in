<?php
/**
 * Copyright 2016 [e-spres-oh]
 * This file is part of Acuma.in
 *
 * Acuma.in is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Acuma.in is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Acuma.in.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AcumaIn\Import;

use GuzzleHttp\Client;


class InstagramLocationImport {
    
    protected $latitude;
    protected $longitude;
    protected $radius;
    
    protected $access_token;
    
    
    public function __construct($latitude, $longitude, $radius, $access_token) {
        
        /* Description: Create a new instance of TwitterImport
         * Input: $longitude - (string) the longitude of the location,
         *        $latitude - (string) the latitude of the location,
         *        $radius - (int) the radius in meters (max 750),
         *        $access_token - (string) the access token to InstagramAPI
         */
        
        
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->radius = min($radius, 750);
        
        $this->access_token = $access_token;
    }
    
    
    public function getLocations() {
        
        /* Description: Search for locations in the given area and save them to the db.
         */
        
        $params = [
            'query' => [
                'access_token' => $this->access_token,
                'lat' => $this->latitude,
                'lng' => $this->longitude,
                'distance' => $this->radius
            ]
        ];
        
        $client = new Client(['base_uri' => 'https://api.instagram.com']);
        $response = $client->request('GET', '/v1/locations/search', $params);
        
        if ($response->getStatusCode() !== 200) {
            throw new \UnexpectedValueException("Response status code returned:\n$response->getStatusCode()\nResponse body:$response->getBody()");
        }
        
        $data = json_decode((string) $response->getBody());
        $this->addToDB($data);
    }
    
    
    private function addToDB($locations) {
        
        /* Description: Add the locations to the db.
         * Input: $locations - (JSON) the list of location and some metadata.
         */
        
        foreach ($locations->data as $location) {
            
            if ($location->id === 0) {
                continue;
            }
            
            $found = \ORM::for_table('ig_location')
                ->where('location_id', $location->id)
                ->find_one();
                
            if ($found !== false) {
                continue;
            }
            
            $new_location = \ORM::for_table('ig_location')->create();
            
            $new_location->location_id = intval($location->id);
            $new_location->latitude = $location->latitude;
            $new_location->longitude = $location->longitude;
            $new_location->name = $location->name;
            
            $new_location->save();
        }
    }
}