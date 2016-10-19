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


class InstagramFacebookLocationImport {
    
    protected $location_id;
    protected $access_token;
    
    
    public function __construct($location_id, $access_token) {
        
        /* Description: Create a new instance of TwitterImport
         * Input: $location_id - Facebook Location ID found by FacebookLocationImport
         *        $access_token - (string) the access token to InstagramAPI
         */
        
        $this->location_id = $location_id;
        $this->access_token = $access_token;
    }
    
    
    public function getLocation() {
        
        /* Description: Search for the location by the given Facebook ID and save it to the db.
         */
        
        $params = [
            'query' => [
                'access_token' => $this->access_token,
                'facebook_places_id' => $this->location_id
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
        
        /* Description: Add the location to the db.
         * Input: $locations - (JSON) the list with the location and some metadata
         *          It contains only the locations we searched for.
         */
        
        foreach ($locations->data as $location) {
            
            if ($location->id === 0) {
                continue;
            }
            
            $found = \ORM::for_table('ig_location')
                ->select('id', 'id')
                ->where('location_id', $location->id)
                ->find_one();
                
            $facebook = \ORM::for_table('fb_location')
                ->select('id')
                ->where('location_id', $this->location_id)
                ->find_one();
                
            if ($found !== false) {
                $ig_location = \ORM::for_table('ig_location')
                    ->find_one($found->id);
                    
                $ig_location->facebook_id = $facebook->id;
                $ig_location->save();
                
                continue;
            }
            
            $new_location = \ORM::for_table('ig_location')->create();
            
            $new_location->location_id = intval($location->id);
            $new_location->latitude = $location->latitude;
            $new_location->longitude = $location->longitude;
            $new_location->name = $location->name;
            $new_location->facebook_id = $facebook->id;
            
            $new_location->save();
        }
    }
}