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
use AcumaIn\Import\TwitterImport;


class ImportTwitterPostsCommand extends Command {
    
    protected function configure() {
        
        $this
            ->setName('twitter:postsImport')
            ->setDescription('Import Twitter posts')
            ->setHelp('This application imports Twitter posts containing images or text only, from a given area.')
            ->addArgument('city', InputArgument::REQUIRED, 'The name of the city from which to import')
            ->addArgument('hashtags', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'A list of hashtags that the tweet should contain.', NULL)
            ->addOption('text_only', 't', InputOption::VALUE_NONE, 'Import tweets that contain only text');
    }
    
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        $hashtags = $input->getArgument('hashtags');
        $text_only = $input->getOption('text_only');
        
        $city = \ORM::for_table('city')
            ->where('city', $input->getArgument('city'))
            ->find_one();
            
        if ($city === False) {
            throw new \InvalidArgumentException('City not found!');
        }
        
        $access_token = \ORM::for_table('access_tokens')
            ->where('platform', 'twitter')
            ->where('type', 'app')
            ->find_one();
            
        if ($access_token === False) {
            throw new \InvalidArgumentException('Access token not found!');
        }

        $importer = new TwitterImport($city, $access_token->access_token, $hashtags, $text_only);
        $importer->getPosts();
    }
}