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
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7;
use Cocur\Slugify\Slugify;
use AcumaIn\CoverBoundingBox;


class InstagramMediaImport {
    
    protected $city;
    protected $access_token;
    
    
    public function __construct($city, $access_token) {
        
        /* Description: Create a new instance of InstagramImport.
         * Input: $city - the city from which to import posts.
         *        $access_token - (string) the access token to InstagramAPI.
         */
        
        $this->city = $city;
        $this->access_token = $access_token;
    }
    
    
    public function importPosts() {
        
        /* Description: Divide the area of the city in small circles and
         * import posts from each one.
         */
        
        $coverBoundingBox =  new CoverBoundingBox($this->city, 5000);
        foreach ($coverBoundingBox->getAllCircleCenters() as $circle) {
            $this->importFrom($circle);
        }
    }
    
    
    protected function importFrom($circle) {
        
        /* Description: Import posts from the given area, process them
         * and save them to db.
         */
        
        $params = $this->getRequestParams($circle);
        
        $client = new Client(['base_uri' => 'https://api.instagram.com']);
        $response = $client->request('GET', '/v1/media/search', $params);
        
        if ($response->getStatusCode() !== 200) {
            throw new \UnexpectedValueException("Response status code returned:\n$response->getStatusCode()\nResponse body:$response->getBody()");
        }
        
        $posts = json_decode((string) $response->getBody());
        $this->addToDB($this->processPosts($posts));
    }
    
    
    protected function processPosts($posts) {
        
        /* Description: Extract only relevant information from each post. */
        
        $processedPosts = [];
        foreach ($posts->data as $post) {
            
            // Skip video posts
            if ($post->type !== 'image') {
                continue;
            }
            
            $processedPost = [
                'post' => [
                    'created_at' => date('Y-m-d h:i:s', $post->created_time),
                    'instagram_id' => $post->caption->id,
                    'text' => $post->caption->text,
                    'tags' => $this->getTags($post->tags),
                    'media_url' => preg_replace('/http:/', 'https:', $post->images->standard_resolution->url),
                    'instagram_url'=> preg_replace('/http:/', 'https:', $post->link),
                    'slug' => $this->getSlug($post)
                ],
                'user' => [
                    'username' => $post->user->username,
                    'profile_picture' => preg_replace('/http:/', 'https:', $post->user->profile_picture),
                    'instagram_id' => $post->user->id
                ]
            ];
            
            $this->setLocation($post->location, $processedPost);
            $this->saveLocation($post->location);
            
            $processedPosts[] = $processedPost;
        }
        
        return $processedPosts;
    }
    
    
    protected function getSlug($post) {
        
        $slugify = new Slugify();
        
        $id = $post->user->username;
        if (isset($post->caption->text)) {
            $id .= '-' . $post->caption->text;
        }
        else {
            $id .= (string) date('Y-m-d h:i:s', $post->created_time);
        }
        
        return $slugify->slugify($id);
    }
    
    
    protected function addToDB($posts) {
        
        /* Description: Add the processed posts to the db. */
        
        foreach ($posts as $post) {
            
            if ($this->postInDB($post)) {
                continue;
            }
            
            $user = $this->addUserToDB($post['user']);
            $post['post']['user_id'] = $user->id;
            $this->addPostToDB($post['post']);
        }
    }
    
    
    protected function addUserToDB($user) {
        
        /* Description: Add the user to db, if it isn't there, and return it. */
        
        $dbUser = \ORM::for_table('ig_user')
            ->where('instagram_id', $user['instagram_id'])
            ->find_one();
            
        if ($dbUser !== false) {
            return $dbUser;
        }
        
        $newUser = \ORM::for_table('ig_user')
            ->create();
            
        $newUser->username = $user['username'];
        $newUser->profile_picture = $user['profile_picture'];
        $newUser->instagram_id = $user['instagram_id'];
        $newUser->save();
        
        $this->addUserToModeration($newUser);
        
        return $newUser;
    }
    
    
    protected function addPostToDB($post) {
        
        /* Description: Add the post to db after being processed. */
        
        $state = $this->userState($post['user_id']);
        // Don't save posts from blocked users
        if ($state === 'blocked') {
            return;
        }
        
        $newPost = \ORM::for_table('ig_post')
            ->create();
            
        $newPost->set($post);
        $newPost->save();
        
        $this->addToTimeline($newPost, $state);
    }
    
    
    protected function addToTimeline($newPost, $state) {
        
        /* Description: Add the post to timeline. */
        
        $newEntry = \ORM::for_table('timeline')->create();
            
        $newEntry->source = 'ig_post';
        $newEntry->source_id = $newPost->id;
        $newEntry->source_timestamp = $newPost->created_at;
        $newEntry->source_user_id = $newPost->user_id;
        $newEntry->blocked = ($state === 'accepted' ? 'no' : 'yes');
        $newEntry->city_id = $this->city->id;
        $newEntry->save();
    }
    
    
    protected function userState($userID) {
        
        /* Description: Return the state of the user from the moderation table */
        
        $moderation = \ORM::for_table('moderation_table')
            ->where('platform', 'instagram')
            ->where('user_id', $userID)
            ->find_one();
            
        return $moderation->state;
    }
    
    
    protected function addUserToModeration($user) {
        
        /* Description: Add a new user to the moderation table. */
        
        $moderate = \ORM::for_table('moderation_table')
            ->create();
                
        $moderate->user_id = $user->id;
        $moderate->platform = 'instagram';
        $moderate->state = 'pending';
        $moderate->save();
    }
    
    
    protected function postInDB($post) {
        
        /* Description: Check if a post is in the db. */
        
        $dbPost = \ORM::for_table('ig_post')
            ->where('instagram_id', $post['post']['instagram_id'])
            ->find_one();
            
        return ($dbPost !== false ? true : false);
    }
    
    
    protected function setLocation($location, &$processedPost) {
        
        /* Description: Add the location to a post if it is set. */
        
        if (isset($location)) {
            $processedPost['post']['location_id'] = $location->id;
        }
    }
    
    
    protected function saveLocation($location) {
        
        /* Description: Save a new location to db. */
        
        if (!$this->locationExistsInDB($location)) {
            $newLocation = \ORM::for_table('ig_location')
                ->create();
                
            $newLocation->location_id = $location->id;
            $newLocation->name = $location->name;
            $newLocation->latitude = $location->latitude;
            $newLocation->longitude = $location->longitude;
            $newLocation->city_id = $this->city->id;
            $newLocation->save();
        }
    }
    
    
    protected function locationExistsInDB($location) {
        
        $dbLocation = \ORM::for_table('ig_location')
            ->where('city_id', $this->city->id)
            ->where('location_id', $location->id)
            ->find_one();
        
        return ($dbLocation !== false ? true : false);
    }
    
    
    protected function getTags($tagsArray) {
        
        return implode(',', $tagsArray);
    }
    
    
    protected function getRequestParams($circle) {
        
        /* Description: Get the request parameters for a call to Instagram API. */
        
        $params = [
            'query' => [
                'access_token' => $this->access_token,
                'lat' => $circle->latitude,
                'lng' => $circle->longitude,
                'distance' => 5000  // maximum value for radius
            ]
        ];
        
        $this->setMinID($params);
        
        return $params;
    }
    
    
    protected function setMinID(&$params) {
        
        /* Description: Add the id of the last imported post or do nothing
         * if no posts exists in db.
         */
        
        $lastImportedPostID = \ORM::for_table('ig_post')
            ->select_expr('MAX(instagram_id)', 'id')
            ->find_one();
            
        if ($lastImportedPostID !== false) {
            $params['query']['min_id'] = $lastImportedPostID->id;
        }
    }
}