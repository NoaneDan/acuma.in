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
use AcumaIn\Auth\FacebookAuth;


class AuthFacebookCommand extends Command {
    
    protected function configure() {
        
        $this
            ->setName('facebook:auth')
            ->setDescription('Authenticate to FacebookGraphAPI')
            ->setHelp('This application generates an app access token for FacebookGraphAPI')
            ->addArgument('client_id', InputArgument::REQUIRED, 'The client id received from Facebook Dev')
            ->addArgument('client_secret', InputArgument::REQUIRED, 'The client secret received from Facebook Dev');
    }
    
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        // get the arguments
        $client_id = $input->getArgument('client_id');
        $client_secret = $input->getArgument('client_secret');
        
        // create and run the importer
        $generator = new FacebookAuth($client_id, $client_secret);
        $generator->generateAccessToken();
    }
}