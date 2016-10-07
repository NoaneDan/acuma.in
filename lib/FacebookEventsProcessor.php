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


class FacebookEventsProcessor {
    
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
        
        $dbElements = \ORM::for_table('fb_event')
            ->table_alias('fbe')
            ->select('fbe.*')
            ->select('fbl.name', 'location')
            ->join('fb_location', ['fbe.location_id', '=', 'fbl.id'], 'fbl')
            ->where_in('fbe.id', Arrays::pluck($this->elements, 'source_id'))
            ->find_array();
            
        $this->extractRelevantData($dbElements);
    }
    
    
    protected function extractRelevantData($dbElements) {
        
        foreach ($dbElements as $dbElement) {
            $this->processedElements["fb_event-$dbElement[event_id]"] = [
                'timestamp' => strtotime($dbElement['start_time']),
                'start_time' => $this->getEventTime($dbElement['start_time']),
                'start_date' => $this->getEventDate($dbElement['start_time']),
                'end_time' => $dbElement['end_time'],
                'description' => $dbElement['description'],
                'name' => $dbElement['name'],
                'facebook_url' => $dbElement['facebook_url'],
                'category' => $dbElement['category'],
                'type' => $dbElement['type'],
                'attending_count' => $dbElement['attending_count'],
                'interested_count' => $dbElement['interested_count'],
                'maybe_count' => $dbElement['maybe_count'],
                'declined_count' => $dbElement['declined_count'],
                'ticket_uri' => $dbElement['ticket_uri'],
                'photos' => [],
                'id' => $dbElement['slug'],
                'location' => $dbElement['location']
            ];
                            
            $photos = \ORM::for_table('fb_photo')
                ->where('event_id', $dbElement['id'])
                ->where('location_id', $dbElement['location_id'])
                ->find_many();
                
            foreach ($photos as $photo) {
                $this->processedElements["fb_event-$dbElement[event_id]"]['photos'][] = "$photo[photo_url]";
            }
        }
    }
    
    
    protected function getEventDate($mysqlTimestamp) {
        
        return date('l jS F Y', strtotime($mysqlTimestamp));
    }
    
    
    protected function getEventTime($mysqlTimestamp) {
        
        return date('H:iA', strtotime($mysqlTimestamp));
    }
}