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


class CoverBoundingBox {
    
    protected $area;
    protected $circleRadiusInMeters;
    
    
    public function __construct($area, $circleRadiusInMeters) {
        
        $this->area = $area;
        $this->circleRadiusInMeters = $circleRadiusInMeters;
    }
    
    
    protected function metersToLatitudeDegrees($meters) {
        
        return $meters / 110574;
    }
    
    
    protected function metersToLongitudeDegrees($meters, $latitude) {
        
        return $meters / (111320 * cos(deg2rad($latitude)));
    }
    
    
    protected function distanceFromCenter() {
        
        return $this->area->radiusInMeters / sqrt(2);
    }
    
    
    protected function boundingBoxUpperLeftCorner() {
        
        $latitude = $this->area->latitude + $this->metersToLatitudeDegrees($this->distanceFromCenter());
        $longitude = $this->area->longitude - $this->metersToLongitudeDegrees($this->distanceFromCenter(), $this->area->latitude);
        $boundingBoxUpperLeftCorner = (object) [
            'latitude' => $latitude,
            'longitude' => $longitude
        ];
        
        return $boundingBoxUpperLeftCorner;
    }
    
    
    protected function boundingBoxBottomRightCorner() {
        
        $latitude = $this->area->latitude - $this->metersToLatitudeDegrees($this->distanceFromCenter());
        $longitude = $this->area->longitude + $this->metersToLongitudeDegrees($this->distanceFromCenter(), $latitude);
        $boundingBoxBottomRightCorner = (object) [
            'latitude' => $latitude,
            'longitude' => $longitude
        ];
        
        return $boundingBoxBottomRightCorner;
    }
    
    
    protected function getLatDistanceBetweenCircles() {
        
        return 3 * $this->circleRadiusInMeters / 2; 
    }
    
    
    protected function getLongDistanceBetweenCircles() {
        
        return sqrt(3) * $this->circleRadiusInMeters;
    }
    
    
    public function getAllCircleCenters() {
        
        /* Description: Cover all the bounding box area with circles.
         * More info: http://stackoverflow.com/questions/7716460/fully-cover-a-rectangle-with-minimum-amount-of-fixed-radius-circles.
         */
        
        $boundingBoxUpperLeftCorner = $this->boundingBoxUpperLeftCorner();
        $boundingBoxBottomRightCorner = $this->boundingBoxBottomRightCorner();
        
        $circle_centers = [];
        for ($x = $boundingBoxUpperLeftCorner->latitude; $x > $boundingBoxBottomRightCorner->latitude; $x -= $this->metersToLatitudeDegrees($this->getLatDistanceBetweenCircles())) {
            for ($y = $boundingBoxUpperLeftCorner->longitude; $y < $boundingBoxBottomRightCorner->longitude; $y += $this->metersToLongitudeDegrees($this->getLongDistanceBetweenCircles(), $x)) {
                $circle_centers[] = (object) [
                    'latitude' => $x,
                    'longitude' => $y
                ];
            }
        }
        
        return $circle_centers;
    }
}