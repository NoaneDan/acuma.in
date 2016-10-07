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
use AcumaIn\TwitterTweetsProcessor;
use AcumaIn\TwitterImagesProcessor;
use AcumaIn\InstagramImagesProcessor;
use AcumaIn\FacebookEventsProcessor;


class BlockLayout {
    
    protected $elements;
    protected $processedElements = [];
    protected $layoutBalance = 0;
    protected $layoutScore = 0;
    protected $done = false;
    
    protected $layoutElements = [];
    protected $elementsThatDontFit = [];
    protected $groupingFn;
    protected $cityID;
    protected $maxID;
    
    
    public function __construct($groupingFn, $cityID, $maxID, $elementsThatDontFit = []) {
        
        $this->groupingFn = $groupingFn;
        $this->cityID = $cityID;
        $this->maxID = $maxID;
        $this->elementsThatDontFit = $elementsThatDontFit;
    }
    
    
    public function getElementsThatDontFit() {
        
        return $this->elementsThatDontFit;
    }
    
    
    public function getLayoutElements() {
        
        while (!$this->done) {
            $this->getElementsFromTimeline();
        
            if (count($this->elements) === 0) {
                $this->addElementsFromQueue();
                break;
            }
            
            $this->groupElementsBySource();
            $this->processElements();    
            $this->sortElementsByDate();
            $this->addElementsToLayout();
        }
        
        return $this->getResponse();
    }
    
    
    protected function getResponse() {
        
        $nrOfPosts = $this->getNumberOfPosts();
        
        $response = (object) [
            'max_id' => $this->maxID,
            'nrOfPosts' => $nrOfPosts,
            'posts' => $this->layoutElements
        ];
        
        return $response;
    }
    
    
    protected function getNumberOfPosts() {
        
        return \ORM::for_table('timeline')
            ->where('city_id', $this->cityID)
            ->where('blocked', 'no')
            ->where_not_equal('source', 'twitter_post')
            ->count();
    }
    
    
    protected function getElementsFromTimeline() {
        
        $time = time();
        
        $this->elements = \ORM::for_table('timeline')
            ->where('city_id', $this->cityID)
            ->where('blocked', 'no')
            ->where_not_equal('source', 'twitter_post')
            ->order_by_expr("abs(unix_timestamp(str_to_date(source_timestamp, '%Y-%m-%d %T')) - $time)")
            ->limit(24)
            ->offset($this->maxID)
            ->find_array();
    }
    
    
    protected function groupElementsBySource() {
        
        $this->elements = Arrays::group($this->elements, function ($element) {
            
            return $element['source']; 
        });
    }
    
    
    protected function processElements() {
        
        $this->processTwitterTweets();
        $this->processTwitterImages();
        $this->processInstagramImages();
        $this->processFacebookEvents();
    }
    
    
    protected function processTwitterTweets() {
        
        if (isset($this->elements['twitter_tweet'])) {
            $twitterTweetsProcessor = new TwitterTweetsProcessor($this->elements['twitter_tweet']);
            $this->addProcessedElements($twitterTweetsProcessor->getProcessedElements());
        }
    }
    
    
    protected function processTwitterImages() {
        
        if (isset($this->elements['twitter_post'])) {
            $twitterImagesProcessor = new TwitterImagesProcessor($this->elements['twitter_post']);
            $this->addProcessedElements($twitterImagesProcessor->getProcessedElements());       
        }
    }
    
    
    protected function processInstagramImages() {
        
        if (isset($this->elements['ig_post'])) {
            $instagramImagesProcessor = new InstagramImagesProcessor($this->elements['ig_post']);
            $this->addProcessedElements($instagramImagesProcessor->getProcessedElements());
        }
    }
    
    
    protected function processFacebookEvents() {
        
        if (isset($this->elements['fb_event'])) {
            $facebookEventsProcessor = new FacebookEventsProcessor($this->elements['fb_event']);
            $this->addProcessedElements($facebookEventsProcessor->getProcessedElements());
        }
    }
    
    
    protected function addProcessedElements($processedElements) {
        
        $this->processedElements  = array_merge($this->processedElements, $processedElements);
    }
    
    
    protected function sortElementsByDate() {
        
        $time = time();
        
        $this->processedElements = Arrays::sort($this->processedElements, function ($element) use ($time) {
            
            return abs($element['timestamp'] - $time);
        });
    }
    
    
    protected function addElementsToLayout() {
        
        while (!$this->noMoreProcessedElements() && ($this->layoutIsUnbalanced() || $this->notEnoughElementsInLayout())) {
            $source = $this->getSourceOfFirstElement();            
            $element = $this->getFirstProcessedElement();
            
            $score = ($source === 'twitter_tweet' ? 2 : 4);
            $this->addElementToLayout($element, $source, $score);
        }
        
        if (!$this->layoutIsUnbalanced() && !$this->notEnoughElementsInLayout()) {
            $this->done = true;
        }
    }
    
    
    protected function getFirstProcessedElement() {
        
        return array_shift($this->processedElements);
    }
    
    
    protected function getSourceOfFirstElement() {
        
        return explode('-', array_keys($this->processedElements)[0])[0];
    }
    
    
    protected function noMoreProcessedElements() {
        
        return $this->processedElements === [];
    }
    
    
    protected function layoutIsUnbalanced() {
        
        return ($this->layoutBalance !== 0 ? true : false);
    }
    
    
    protected function notEnoughElementsInLayout() {
        
        return ($this->layoutScore < 24 ? true : false);
    }
    
    
    public function addElementToLayout($element, $source, $score) {
        
        $groupingFn = $this->groupingFn;
        $group = $groupingFn($element, $source);
        
        if ($source !== 'ig_post') {
            if (isset($this->layoutElements[$source.'-'.$group])) {
                $this->layoutElements[$source.'-'.$group][] = $element;
            }
            else {
                $this->layoutElements[$source.'-'.$group] = [$element];
                
                $this->layoutScore += $score;
                $this->layoutBalance += -$this->sign($this->layoutBalance) * $score;
            }
        }
        else {
            $this->addElementToQueue($element, $source, $group);
            $this->tryAddElementsFromQueue();
        }
        
        $this->maxID++;
    }
    
    
    public function addElementToQueue($element, $source, $group) {
        
        $this->elementsThatDontFit[$source.'-'.$group] = [$element];
    }
    
    
    public function tryAddElementsFromQueue() {
        
        if (count($this->elementsThatDontFit) == 2) {
            foreach ($this->elementsThatDontFit as $key => $element) {
                $this->layoutElements[$key] = $element;
                
                unset($this->elementsThatDontFit[$key]);
                
                $this->layoutScore++;
                $this->layoutBalance += -$this->sign($this->layoutBalance);
            }
        }
    }
    
    
    public function addElementsFromQueue() {
        
        foreach ($this->elementsThatDontFit as $key => $element) {
            $this->layoutElements[$key] = $element;
            
            unset($this->elementsThatDontFit[$key]);
            
            $this->layoutScore++;
            $this->layoutBalance += -$this->sign($this->layoutBalance);
        }
    }
    
    
    protected function sign($num) {
        
        return ($num >= 0 ? 1 : -1);
    }
}