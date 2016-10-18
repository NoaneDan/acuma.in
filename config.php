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


require_once __DIR__.'/vendor/autoload.php';
$environment = require_once __DIR__ . '/config/auto.environment.php';

setlocale(LC_CTYPE, 'ro_RO');

ORM::configure(sprintf("mysql:host=localhost;dbname=%s", $environment['mysql']['database']));
ORM::configure('username', $environment['mysql']['username']);
ORM::configure('password', $environment['mysql']['password']);

function exception_error_handler($severity, $message, $file, $line) {
    
    if (!(error_reporting()) & $severity) {
        // This error code is not included in error_reporting
        return;
    }
    
    throw new ErrorException($message, 0, $severity, $file, $line);
}
set_error_handler('exception_error_handler', E_ALL & ~E_USER_DEPRECATED);
