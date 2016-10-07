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
use AcumaIn\Import\InstagramImport;


class ImportInstagramMediaCommand extends Command {
    
    protected function configure() {
        
        $this
            ->setName('instagram:mediaImport')
            ->setDescription('Import media from instagram')
            ->setHelp('This application imports images and videos from Instagram from a given location or a given tag name')
            ->addArgument('search_param', InputArgument::REQUIRED, 'The Instagram ID of the location or the tag name');
    }
    
    
    protected function execute(InputInterface $input, OutputInterface $output) {
     
        // get the arguments
        $search_param = $input->getArgument('search_param');
        
        $is_location = \ORM::for_table('ig_location')
            ->where('location_id', $search_param)
            ->find_one();
            
        $tag = ($is_location === False ? False : True);
    
        $access_token = \ORM::for_table('access_tokens')
            ->where('platform', 'instagram')
            ->where('type', 'app')
            ->find_one();
            
        if ($access_token === False) {
            throw new \InvalidArgumentException('Access token not found!');
        }
        
        // create and run the importer
        $importer = new InstagramImport($search_param, $access_token->access_token, $tag);
        $importer->getPosts();
    }
}