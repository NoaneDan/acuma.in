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
namespace AcumaIn\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Cocur\Slugify\Slugify;


class SlugifyCommand extends Command {
    
    protected function configure() {
        
        $this
            ->setName('slugify')
            ->setDescription('Generate slugs')
            ->setHelp('This application generates slugs for all the posts and events.');
    }
    
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        $this->generateSlugsForFacebookEvents();
        $this->generateSlugsForInstagramPosts();
        $this->generateSlugsForTwitterPosts();
        $this->generateSlugsForTwitterTweets();
    }
    
    
    protected function generateSlugsForFacebookEvents() {
        
        $slugify = new Slugify();
        
        $events = \ORM::for_table('fb_event')
            ->find_many();
            
        foreach ($events as &$event) {
            $event->slug = $slugify->slugify($event->name);
            $event->save();
        }
    }
    
    protected function generateSlugsForInstagramPosts() {
        
        $slugify = new Slugify();
        
        $posts = \ORM::for_table('ig_post')
            ->find_many();
            
        foreach ($posts as &$post) {
            $user = \ORM::for_table('ig_user')
                ->where('id', $post->user_id)
                ->find_one();
                
            $id = $user->username;
            if (isset($post->text)) {
                $id .= '-' . $post->text;
            }
            else {
                $id .= (string) date('Y-m-d h:i:s', $post->created_at);
            }
            
            $post->slug = $slugify->slugify($id);
            $post->save();
        }
    }
    
    protected function generateSlugsForTwitterPosts() {
        
        $slugify = new Slugify();
        
        $posts = \ORM::for_table('twitter_post')
            ->find_many();
            
        foreach ($posts as &$post) {
            $user = \ORM::for_table('twitter_user')
                ->where('id', $post->user_id)
                ->find_one();
                
            $id = $user->screenname;
            if (isset($post->text)) {
                $id .= '-' . $post->text;
            }
            else {
                $id .= (string) date('D F d H:i:s P Y', $post->created_at);
            }
            
            $post->slug = $slugify->slugify($id);
            $post->save();
        }
    }
    
    protected function generateSlugsForTwitterTweets() {
        
        $slugify = new Slugify();
        
        $tweets = \ORM::for_table('twitter_tweet')
            ->find_many();
            
        foreach ($tweets as &$tweet) {
            $user = \ORM::for_table('twitter_user')
                ->where('id', $tweet->user_id)
                ->find_one();
                
            $id = $user->screenname;
            if (isset($tweet->text)) {
                $id .= '-' . $tweet->text;
            }
            else {
                $id .= (string) date('D F d H:i:s P Y', $tweet->created_at);
            }
            
            $tweet->slug = $slugify->slugify($id);
            $tweet->save();
        }
    }
}