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
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7;

class TwitterAuth {
    
    protected $consumer_key;
    protected $consumer_secret;
    protected $bearer_token;
    
    public function __construct($consumer_key, $consumer_secret, $bearer_token = null) {
        
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
        $this->bearer_token = $bearer_token;
    }
    
    private function getCredentials() {
        
        return base64_encode(urlencode($this->consumer_key) . ':' . urlencode($this->consumer_secret));
    }
    
    public function obtainBearerToken() {
        
        if (isset($this->bearer_token)) {
            return;
        }
        
        $credentials = $this->getCredentials();
    
        $params = [
            'form_params' => [
                'grant_type' => 'client_credentials'
            ],
            'headers' => [
                'Authorization' => "Basic $credentials",
                'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
                'User-Agent' => 'Twitter Test App'
            ]
        ];
        
        $client = new Client(['base_uri' => 'https://api.twitter.com']);
        $res = $client->request('POST', '/oauth2/token', $params);
        
        if ($res->getStatusCode() !== 200) {
            throw new \UnexpectedValueException("Response status code returned:\n$res->getStatusCode()\nResponse body:$res->getBody()");
        }
        
        $response = json_decode((string) $res->getBody());
        
        $access_token = \ORM::for_table('access_tokens')
            ->where('platform', 'twitter')
            ->where('type', 'app')
            ->find_one();
            
        if ($access_token !== false) {
            $access_token->access_token = $response->access_token;
            $access_token->save();
        }
        else {
            $new_access_token = \ORM::for_table('access_tokens')
                ->create();
                
            $new_access_token->platform = 'twitter';
            $new_access_token->type = 'app';
            $new_access_token->access_token = $response->access_token;
            $new_access_token->save();
        }
    }
    
    public function invalidateBearerToken() {
        
        if (!isset($this->bearer_token)) {
            return;
        }
        
        $credentials = $this->getCredentials();
        
        $params = [
            'form_params' => [
                'access_token' => '$this->bearer_token'
            ],
            'headers' => [
                'Authorization' => "Basic $credentials",
                'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
                'User-Agent' => 'Twitter Test App',
                'Accept' => '*/*'
            ]
        ];
        
        $client = new Client(['base_uri' => 'https://api.twitter.com']);
        $res = $client->request('POST', '/oauth2/invalidate_token', $params);
        
        if ($res->getStatusCode() !== 200) {
            throw new \UnexpectedValueException("Response status code returned:\n$response->getStatusCode()\nResponse body:$response->getBody()");
        }
    }
}