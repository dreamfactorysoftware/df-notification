<?php

/*
 * This file is part of NotificationPusher.
 *
 * (c) 2013 Cédric Dugat <cedric@dugat.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sly\NotificationPusher\Console;

use Sly\NotificationPusher\Console\Command\PushCommand;
use Sly\NotificationPusher\NotificationPusher;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * @uses \Symfony\Component\Console\Application
 * @author Cédric Dugat <cedric@dugat.me>
 */
class Application extends BaseApplication
{
    public function __construct()
    {
        error_reporting(-1);

        parent::__construct('NotificationPusher version', NotificationPusher::VERSION);

        $this->add(new PushCommand());
    }
}
