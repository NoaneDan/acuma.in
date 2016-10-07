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

class TwitterImport {
    
    protected $city;
    protected $hashtags = [];
    
    protected $query;
    protected $table;
    protected $text_only;
    
    protected $access_token;
    
    public function __construct($city, $access_token, $hashtags = NULL, $text_only = false) {
        
        /* Description: Create a new instance of TwitterImport
         * Input: $city - the city from which to import posts.
         *        $access_token - (string) the access token to TwitterAPI.
         */
        
        
        $this->city = $city;
    
        $this->text_only = $text_only;
        $this->hashtags = $hashtags;
        
        $this->access_token = $access_token;

        $this->query = $hashtags ? ('#'.implode(' OR #', $this->hashtags)) : '';
        if ($this->text_only) {
            $this->query .= ' -filter:media -filter:links';
            $this->table = 'twitter_tweet';
        }
        else {
            $this->query .= ' filter:twimg';
            $this->table = 'twitter_post';
        }
    }
    
    public function getPosts() {
        
        /* Description: Import posts from Twitter containing only images from
         * the given area.
         * Output: $processed_posts - (array) all the recent posts from the area.
         */
        
        $radiusInKm = $this->radiusFromMetersToKm($this->city->radiusInMeters);
        $geocode = $this->getGeocode();
        $params = [
            'query' => [
                'q' => $this->query,
                'geocode' => $geocode,
                'result_type' => 'recent',
                'count' => 100
            ],
            'headers' => [
                'User-Agent' => 'Twitter Test App',
                'Accept-Encoding' => 'gzip',
                'Authorization' => "Bearer $this->access_token"
            ]
        ];
        
        $last_post = \ORM::for_table($this->table)
            ->select_expr('MAX(twitter_id)', 'id')
            ->find_one();
        if ($last_post->id !== NULL) {
            $params['query']['since_id'] = $last_post->id;
        }
        
        $client = new Client(['base_uri' => 'https://api.twitter.com']);
        do {
            $response = $client->request('GET', '/1.1/search/tweets.json', $params);
            
            if ($response->getStatusCode() !== 200) {
                throw new \UnexpectedValueException("Response status code returned:\n$response->getStatusCode()\nResponse body:$response->getBody()");
            }
            
            $posts = json_decode((string) $response->getBody());
            $this->addToDB($this->processPosts($posts));
            
            if (count($posts->statuses) !== 0) {
                $max_id = $posts->statuses[count($posts->statuses) -1]->id - 1;
                $params['query']['max_id'] = $max_id;
            }
        } while (count($posts->statuses) != 0 && $last_post->id !== NULL);
    }
    
    
    private function processPosts($posts) {
        
        /* Description: Select only relevant data from Twitter posts.
         * Input: $posts - (JSON) contains a list of posts and additional metadata.
         * Output: $processed_posts - (array) all the posts after being processed.
         */
        
        $processed_posts = [];
    
        foreach ($posts->statuses as $post) {
            
            // skip post that are retweets
            if (isset($post->retweeted_status) || preg_match('/^RE @(.)+:/', $post->text)) {
                continue;
            }
            
            // skip if it doesn't contain the media field
            if (!isset($post->entities->media) && $this->text_only === false) {
                continue;
            }
            
            // transform the date to timestamp
            $created_at = \DateTime::createFromFormat('D F d H:i:s P Y', $post->created_at);
            $timestamp = $created_at->format('Y-m-d H:i:s');
            
            // create a list of hashtags
            $hashtags = [];
            foreach ($post->entities->hashtags as $hashtag) {
                $hashtags[] = $hashtag->text;
            }
            
            // save relevant data from a post
            $processed_post = [
                'post' => [
                    'created_at' => $timestamp,
                    'twitter_id' => $post->id,
                    'text' => $post->text,
                    'hashtags' => implode(',', $hashtags),
                    'language' => $post->metadata->iso_language_code,
                    'user_id' => null,
                    'slug' => $this->getSlug($post)
                ],
                'user' => [
                    'name' => $post->user->name,
                    'screenname' => $post->user->screen_name,
                    'profile_img' => preg_replace('/http:/', 'https:', $post->user->profile_image_url),
                    'twitter_id' => $post->user->id
                ]
            ];
            
            if ($this->text_only === false) {
                $processed_post['post']['image_url'] = preg_replace('/http:/', 'https:', $post->entities->media[0]->media_url);
                $processed_post['post']['twitter_url'] = preg_replace('/http:/', 'https:', $post->entities->media[0]->expanded_url);
            }
            else {
                $processed_post['post']['twitter_url'] = 'https://twitter.com/'.$post->user->screen_name.'/status/'.$post->id;
            }
            
            $processed_posts[] = $processed_post;
        }
        
        return $processed_posts;
    }
    
    
    protected function getSlug($post) {
        
        $slugify = new Slugify();
        
        $id = $post->user->screen_name;
        if (isset($post->text)) {
            $id .= '-' . $post->text;
        }
        else {
            $id .= (string) date('D F d H:i:s P Y', $post->created_at);
        }
        
        return $slugify->slugify($id);
    }
    
    
    private function addToDB($processed_posts) {
        
        /* Description: Adds processed posts to DB.
         * Input: $processed_posts - (array) a list of processed posts.
         */
        
        foreach ($processed_posts as $post) {
            
            // insert user into db if he doesn't exist and set $post['post']['user_id']
            // to the id of the user
            $user = \ORM::for_table('twitter_user')
                ->select('id')
                ->where('twitter_id', $post['user']['twitter_id'])
                ->find_one();
                
            if ($user && $user->id !== NULL) {
                $post['post']['user_id'] = intval($user->id);
                
                $moderate = \ORM::for_table('moderation_table')
                    ->where('platform', 'twitter')
                    ->where('user_id', $user->id)
                    ->find_one();
            }
            else {
                $new_user = \ORM::for_table('twitter_user')->create();
                
                $new_user->set($post['user']);
                $new_user->save();
                
                $post['post']['user_id'] = $new_user->id;
                
                $moderate = \ORM::for_table('moderation_table')->create();
                
                $moderate->user_id = $new_user->id;
                $moderate->platform = 'twitter';
                $moderate->state = 'pending';
                $moderate->save();
            }
            
            // if user is blocked don't add post
            if ($moderate->state === 'blocked') {
                continue;
            }
            
            // insert post into db
            $new_post = \ORM::for_table($this->table)->create();
            
            $new_post->set($post['post']);
            $new_post->save();
            
            // insert post into timeline table
            $new_entry = \ORM::for_table('timeline')->create();
            
            $new_entry->source = $this->text_only ? 'twitter_tweet' : 'twitter_post';
            $new_entry->source_id = $new_post->id;
            $new_entry->source_timestamp = $new_post->created_at;
            $new_entry->source_user_id = $new_post->user_id;
            $new_entry->blocked = ($moderate->state === 'accepted' ? 'no' : 'yes');
            $new_entry->city_id = $this->city->id;
            $new_entry->save();
        }
    }
    
    
    protected function radiusFromMetersToKm() {
        
        return $this->city->radiusInMeters / 1000;
    }
    
    
    protected function getGeocode() {
        
        return (string) $this->city->latitude . ',' . (string) $this->city->longitude . ',' .(string) $this->radiusFromMetersToKm() . "km";
    }
}