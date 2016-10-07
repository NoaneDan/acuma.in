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
use AcumaIn\Auth\TwitterAuth;


class AuthTwitterCommand extends Command {
    
    protected function configure() {
        
        $this
            ->setName('twitter:auth')
            ->setDescription('Authenticate to TwitterAPI')
            ->setHelp('This application generates an app access token for TwitterAPI')
            ->addArgument('consumer_key', InputArgument::REQUIRED, 'The consumer key received from Twitter')
            ->addArgument('consumer_secret', InputArgument::REQUIRED, 'The consumer secret received from Twitter')
            ->addArgument('bearer_token', InputArgument::OPTIONAL, 'A previously generated access token (only if you want to invalidate it');
    }
    
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        $consumer_key = $input->getArgument('consumer_key');
        $consumer_secret = $input->getArgument('consumer_secret');
        $bearer_token = $input->getArgument('bearer_token');
        
        if ($bearer_token) {
            $generator = new TwitterAuth($consumer_key, $consumer_secret, $bearer_token);
            $generator->invalidateBearerToken();
        }
        else {
            $generator = new TwitterAuth($consumer_key, $consumer_secret);
            $generator->obtainBearerToken();
        }
    }
}