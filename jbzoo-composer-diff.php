<?php

/**
 * JBZoo Toolbox - Composer-Diff
 *
 * This file is part of the JBZoo Toolbox project.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Composer-Diff
 * @license    MIT
 * @copyright  Copyright (C) JBZoo.com, All rights reserved.
 * @link       https://github.com/JBZoo/Composer-Diff
 */

use JBZoo\ComposerDiff\Commands\DiffAction;
use Symfony\Component\Console\Application;

define('PATH_ROOT', __DIR__);

$vendorPaths = [
    __DIR__ . '/../../autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php'
];

foreach ($vendorPaths as $file) {
    if (file_exists($file)) {
        define('PHPUNIT_COMPOSER_INSTALL', $file);
        break;
    }
}

require PHPUNIT_COMPOSER_INSTALL;

$application = new Application();
$application->add(new DiffAction());
$application->setDefaultCommand('diff');
$application->run();
