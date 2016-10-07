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
use Symfony\Component\Console\Exception\InvalidArgumentException;
use AcumaIn\Import\FacebookLocationImport;


class ImportFacebookLocationsCommand extends Command {
    
    protected function configure() {
        
        $this
            ->setName('facebook:locationImport')
            ->setDescription('Import locations from Facebook')
            ->setHelp('This application imports locations from Facebook base on a given area')
            ->addArgument('city', InputArgument::REQUIRED, 'The name of the city from which to import');
    }
    
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        // find the city
        $city_name = $input->getArgument('city');
        $city = \ORM::for_table('city')
            ->where('city', $city_name)
            ->find_one();
            
        if ($city === False) {
            throw new \InvalidArgumentException('City not found!');
        }
        
        $access_token = \ORM::for_table('access_tokens')
            ->where('platform', 'facebook')
            ->where('type', 'app')
            ->find_one();
            
        if ($access_token === False) {
            throw new \InvalidArgumentException('Access token not found!');
        }
        
        // create an run the importer
        $importer = new FacebookLocationImport($city, $access_token->access_token);
        $importer->getAllLocations();
    }
}