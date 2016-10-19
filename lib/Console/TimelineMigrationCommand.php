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


class TimelineMigrationCommand extends Command {
    
    protected function configure() {
        
        $this
            ->setName('timelineMigration')
            ->setDescription('Migrate data to timeline table')
            ->setHelp('This application adds data from a table with posts to the timeline table')
            ->addArgument('from', InputArgument::REQUIRED, 'The name of the table from which to take data');
    }
    
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        $from = $input->getArgument('from');
        
        $posts = \ORM::for_table($from)
            ->select("$from.*")
            ->left_outer_join('timeline', "$from.id = timeline.source_id and timeline.source = '$from'")
            ->where_null('timeline.id')
            ->find_many();
            
        foreach ($posts as $post) {
            
            $new_entry = \ORM::for_table('timeline')
                ->create();
                
            $new_entry->source = $from;
            $new_entry->source_id = $post->id;
            $new_entry->source_user_id = (isset($post->user_id) ? $post->user_id : $post->event_id);
            $new_entry->source_timestamp = (isset($post->created_at) ? $post->created_at : $post->start_time);
            $new_entry->save();
        }
    }
}