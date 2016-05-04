<?php

namespace jonag\Episodes\Command;

use jonag\Episodes\Application;

class Command extends \Symfony\Component\Console\Command\Command
{
    /**
     * @return Application
     */
    public function getApplication()
    {
        return parent::getApplication();
    }
}