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
namespace AcumaIn;

use Underscore\Types\Arrays;
use Cocur\Slugify\Slugify;


class TwitterImagesProcessor {
    
    protected $processedElements = [];
    protected $elements;
    
    
    public function __construct($elements) {
        
        $this->elements = $elements;
    }
    
    
    public function getProcessedElements() {
        
        $this->extractElements();
        
        return $this->processedElements;
    }
    
    
    protected function extractElements() {
        
        $dbElements = \ORM::for_table('twitter_post')
            ->table_alias('tp')
            ->select('tp.*')
            ->select('tu.screenname')
            ->select('tu.profile_img')
            ->select('tu.name')
            ->join('twitter_user', ['tp.user_id', '=', 'tu.id'], 'tu')
            ->where_in('tp.id', Arrays::pluck($this->elements, 'source_id'))
            ->find_array();
            
        $this->extractRelevantData($dbElements);
    }
    
    
    protected function extractRelevantData($dbElements) {
        
        foreach ($dbElements as $dbElement) {
            $hashtags = array_map(function ($tag) {
                return '#'.$tag;    
            },
            explode(' ', $dbElement['hashtags']));
            $hashtags = implode(' ', $hashtags);
            
            $this->processedElements["twitter_post-$dbElement[twitter_id]"] = [
                    'timestamp' => strtotime($dbElement['created_at']),
                    'created_at' => $this->getPostDate($dbElement['created_at']),
                    'text' => $dbElement['text'],
                    'hashtags' => $hashtags,
                    'type' => 'image',
                    'media_url' => $dbElement['image_url'],
                    'username' => $dbElement['screenname'],
                    'name' => $dbElement['name'],
                    'url' => $dbElement['twitter_url'],
                    'profile_img' => $dbElement['profile_img'],
                    'id' => $dbElement['slug'],
                    'report_id' => "twitter_post-$dbElement[id]"
              ];
        }
    }
    
    
    protected function getPostDate($mysqlTimestamp) {
        
        return date('H:iA l jS F Y', strtotime($mysqlTimestamp));
    }
}