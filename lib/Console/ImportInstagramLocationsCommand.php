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
use AcumaIn\Import\InstagramLocationImport;


class ImportInstagramLocationsCommand extends Command {
    
    protected function configure() {
        
        $this
            ->setName('instagram:locationsImport')
            ->setDescription('Import locations from Instagram')
            ->setHelp('This application imports locations from Instagram based on a given area')
            ->addArgument('latitude', InputArgument::REQUIRED, 'The latitude of the center of the area')
            ->addArgument('longitude', InputArgument::REQUIRED, 'The longitude of the center of the area')
            ->addArgument('radius', InputArgument::OPTIONAL, 'The radius in meters (between 0 and 750)', 750);
    }
    
    
    protected function execute(InputInterface $input, OutputInterface $output) {
             
        // get the rest of the arguments
        $latitude = $input->getArgument('latitude');
        $longitude = $input->getArgument('longitude');
        $radius = $input->getArgument('radius');
        
        $access_token = \ORM::for_table('access_tokens')
            ->where('platform', 'instagram')
            ->where('type', 'app')
            ->find_one();
            
        if ($access_token === False) {
            throw new \InvalidArgumentException('Access token not found!');
        }
        
        // create an run the importer
        $importer = new InstagramLocationImport($latitude, $longitude, $radius, $access_token->access_token);
        $importer->getLocations();
    }
}