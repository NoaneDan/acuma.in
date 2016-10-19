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

namespace AcumaIn\Auth;

use GuzzleHttp\Client;


class FacebookAuth {
    
    protected $client_id;
    protected $client_secret;
    
    
    public function __construct($client_id, $client_secret) {
        /* Description: Create a new instance of FacebookAuth.
         * Input: $client_id - the id of the app received from Facebook Developer
         *        $client_secret - the application secret received from Facebook Developer
         */
        
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
    }
    
    
    public function generateAccessToken() {
        /* Description: Make a get request for a new application access token.
         * The access token expires in 60 days.
         */
        
        $params = [
            'query' => [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'client_credentials'
            ]
        ];
        
        $client = new Client(['base_uri' => 'https://graph.facebook.com']);
        $response = $client->request('GET', '/oauth/access_token', $params);
        
        if ($response->getStatusCode() !== 200) {
            throw new \UnexpectedValueException(sprintf(
                "Response status code returned: %s\nResponse body: %s\n",
                $response->getStatusCode(),
                $response->getBody()
            ));
        }
        
        parse_str((string) $response->getBody());
        $fb_access_token = $access_token;
    
        $access_token = \ORM::for_table('access_tokens')
            ->where('platform', 'facebook')
            ->where('type', 'app')
            ->find_one();
            
        if ($access_token !== false) {
            $access_token->access_token = $fb_access_token;
            $access_token->save();
        }
        else {
            $new_access_token = \ORM::for_table('access_tokens')
                ->create();
                
            $new_access_token->platform = 'facebook';
            $new_access_token->type = 'app';
            $new_access_token->access_token = $fb_access_token;
            $new_access_token->save();
        }
    }
}