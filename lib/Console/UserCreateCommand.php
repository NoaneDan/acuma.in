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


class UserCreateCommand extends Command {
    
    protected function configure() {
        $this
            ->setName('user:create')
            ->setDescription('Create a new administrative user')
            ->addArgument('username', InputArgument::REQUIRED, 'Desired username')
            ->addArgument('password', InputArgument::REQUIRED, 'Desired password');
    }
    
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        $username = $input->getArgument('username');
        
        $user = \ORM::for_table('user')->create();
        $user->set('username', $username);
        $user->set('password', password_hash($input->getArgument('password'), PASSWORD_BCRYPT));
        $user->save();
        
        $output->writeln(sprintf("Created user account for %s", $username));
    }
}