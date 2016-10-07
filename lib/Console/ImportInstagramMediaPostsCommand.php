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
use AcumaIn\Import\InstagramMediaImport;


class ImportInstagramMediaPostsCommand extends Command {
    
    protected function configure() {
        
        $this
            ->setName('instagram:mediaPostsImport')
            ->setDescription('Import media from instagram')
            ->setHelp('This application imports images and videos from Instagram from a given location or a given tag name')
            ->addArgument('city', InputArgument::REQUIRED, 'The city from which to import posts');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        
        $city = $this->getCity($input->getArgument('city'));
        $access_token = $this->getAccessToken();
        
        $importer = new InstagramMediaImport($city, $access_token->access_token);
        $importer->importPosts();
    }
    
    
    protected function getCity($cityName) {
        
        $city = \ORM::for_table('city')
            ->where('city', $cityName)
            ->find_one();
            
        if ($city === False) {
            throw new \InvalidArgumentException('City not found!');
        }
        
        return $city;
    }
    
    
    protected function getAccessToken() {
        
        $access_token = \ORM::for_table('access_tokens')
            ->where('platform', 'instagram')
            ->where('type', 'app')
            ->find_one();
            
        if ($access_token === False) {
            throw new \InvalidArgumentException('Access token not found!');
        }
        
        return $access_token;
    }
}