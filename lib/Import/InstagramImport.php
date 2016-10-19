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


class InstagramImport {
    
    protected $search_param;
    protected $access_token;
    protected $is_tag;
    
    
    public function __construct($search_param, $access_token, $is_tag = false) {
        
        /* Description: Create a new instance of InstagramImport.
         * Input: $search_param - (string) the id of the location from which to import
         *          posts or a tag name.
         *        $access_token - (string) the access token to InstagramAPI.
         *        $tag - true if the $search_param is a tag name or false if it is a location id.
         */
        
        $this->search_param = $search_param;
        $this->access_token = $access_token;
        $this->is_tag = $is_tag;
    }
    
    
    public function getPosts() {
        
        /* Description: Import posts from Instagram containing videos or images from
         * a given location, process them and save them to db.
         */
        
        $params = [
            'query' => [
                'access_token' => $this->access_token,
            ]
        ];
        
        // seach for the id of the last imported post.
        if ($this->is_tag === False) {
            $from = 'locations';
            
            $min_id = \ORM::for_table('ig_post')
                ->select_expr('MAX(instagram_id)', 'id')
                ->where('location_id', $this->search_param)
                ->find_one();
        }
        else {
            $from = 'tags';
            
            $min_id = \ORM::for_table('ig_post')
                ->select_expr('MAX(instagram_id)', 'id')
                ->where('tags', $this->search_param)
                ->find_one();
        }
        
        if ($min_id !== False) {
            $params['query']['min_id'] = $min_id->id;
        }
        
        $client = new Client(['base_uri' => 'https://api.instagram.com']);
        $response = $client->request('GET', "/v1/$from/$this->search_param/media/recent", $params);
        
        if ($response->getStatusCode() !== 200) {
            throw new \UnexpectedValueException("Response status code returned:\n$response->getStatusCode()\nResponse body:$response->getBody()");
        }
        
        $posts = json_decode((string) $response->getBody());
        $this->addToDB($this->processPosts($posts));
    }
    
    
    private function processPosts($posts) {
        
        /* Description: Select only relevant data from Instagram posts.
         * Input: $posts - (JSON) contains a list of posts and additional metadata.
         * Output: $processed_posts - (array) all the posts after being processed.
         */
        
        $processedPosts = [];
        foreach ($posts->data as $post) {
            // check if the location is in Oradea
            if (!$this->is_tag && isset($post->location)) {
                $location = \ORM::for_table('ig_location')
                    ->where('location_id', $post->location->id)
                    ->find_one();
                    
                if ($location === False) {
                    continue;
                }
            }
            
            $processedPost = [
                'post' => [
                    'created_at' => date('Y-m-d h:i:s', $post->created_time),
                    'instagram_id' => $post->caption->id,
                    'text' => $post->caption->text,
                    'type' => $post->type,
                    'instagram_url' => $post->link,
                ],
                'user' => [
                    'username' => $post->user->username,
                    'profile_picture' => $post->user->profile_picture,
                    'instagram_id' => $post->user->id
                ]
            ];
            
            if ($this->is_tag === True) {
                $processedPost['post']['tags'] = $this->search_param;
            }
            else {
                $processedPost['post']['tags'] =  implode(',', $post->tags);
                $processedPost['post']['location_id'] =  $this->search_param;
            }
            
            if ($post->type === 'image') {
                $processedPost['post']['media_url'] = $post->images->standard_resolution->url;
            }
            else {
                $processedPost['post']['media_url'] = $post->videos->standard_resolution->url;
            }
            
            $processedPosts[] = $processedPost;
        }
        
        return $processedPosts;
    }
    
    
    private function addToDB($posts) {
        
        /* Description: Adds processed posts to DB.
         * Input: $processed_posts - (array) a list of processed posts.
         */
        
        foreach ($posts as $post) {
            
            // skip the post if already exists in db.
            // this is needed because the last imported post is returned again.
            $found = \ORM::for_table('ig_post')
                ->where('instagram_id', $post['post']['instagram_id'])
                ->find_one();
                
            if ($found !== False) {
                continue;
            }
            
            // search for the user
            $user = \ORM::for_table('ig_user')
                ->where('instagram_id', $post['user']['instagram_id'])
                ->find_one();
                
            // check if is in the db and set the 'user_id' of the post
            // with the id of the user
            if ($user !== false) {
                $post['post']['user_id'] = $user->id;
                
                $moderate = \ORM::for_table('moderation_table')
                    ->where('platform', 'ig_post')
                    ->where('user_id', $user->id)
                    ->find_one();
            }
            else {
                $new_user = \ORM::for_table('ig_user')
                    ->create();
                    
                $new_user->username = $post['user']['username'];
                $new_user->profile_picture = $post['user']['profile_picture'];
                $new_user->instagram_id = $post['user']['instagram_id'];
                $new_user->save();
                    
                $post['post']['user_id'] = $new_user->id;
                
                $moderate = \ORM::for_table('moderation_table')->create();
                
                $moderate->user_id = $new_user->id;
                $moderate->platform = 'instagram';
                $moderate->state = 'pending';
                $moderate->save();
            }
            
            // if user is blocked don't add post
            if ($moderate->state === 'blocked') {
                continue;
            }
            
            // add to db
            $new_post = \ORM::for_table('ig_post')
                ->create();
            
            $new_post->set($post['post']);
            $new_post->save();
            
            // insert post into timeline table
            $new_entry = \ORM::for_table('timeline')->create();
            
            $new_entry->source = 'ig_post';
            $new_entry->source_id = $new_post->id;
            $new_entry->source_timestamp = $new_post->created_at;
            $new_entry->source_user_id = $new_post->user_id;
            $new_entry->blocked = ($moderate->state === 'accepted' ? 'no' : 'yes');
            $new_entry->save();
        }
    }
}