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


class InstagramImagesProcessor {
    
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
        
        $dbElements = \ORM::for_table('ig_post')
            ->table_alias('igp')
            ->select('igp.*')
            ->select('igu.username')
            ->select('igu.profile_picture')
            ->join('ig_user', ['igp.user_id', '=', 'igu.id'], 'igu')
            ->where_in('igp.id', Arrays::pluck($this->elements, 'source_id'))
            ->find_array();
            
        $this->extractRelevantData($dbElements);
    }
    
    
    protected function extractRelevantData($dbElements) {
        
        foreach ($dbElements as $dbElement) {
            $hashtags = array_map(function ($tag) {
                return '#'.$tag;    
            },
            explode(' ', $dbElement['tags']));
            $hashtags = implode(' ', $hashtags);
                
             $this->processedElements["ig_post-$dbElement[instagram_id]"] = [
                'timestamp' => strtotime($dbElement['created_at']),
                'created_at' => $dbElement['created_at'],
                'text' => $dbElement['text'],
                'hashtags' => $hashtags,
                'type' => $dbElement['type'],
                'media_url' => $dbElement['media_url'],
                'instagram_url' => $dbElement['instagram_url'],
                'username' => $dbElement['username'],
                'profile_img' => $dbElement['profile_picture'],
                'id' => $dbElement['slug'],
                'report_id' => "ig_post-$dbElement[id]" 
            ];
        }
    }
}