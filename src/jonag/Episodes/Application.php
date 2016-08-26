<?php

namespace jonag\Episodes;

use jonag\Episodes\Command\AddToDatabaseCommand;
use jonag\Episodes\Command\InitDatabaseCommand;
use jonag\Episodes\Command\MoveEpisodesCommand;
use jonag\Episodes\Command\SearchMissingSubtitlesCommand;
use jonag\Episodes\Command\SearchSubtitlesCommand;
use Pimple\Container;

class Application extends \Symfony\Component\Console\Application
{
    private $container;

    /**
     * Application constructor.
     * @param string $name
     * @param string $version
     */
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);
        $this->container = new Container();

        $this->add(new MoveEpisodesCommand());
        $this->add(new SearchSubtitlesCommand());
        $this->add(new InitDatabaseCommand());
        $this->add(new AddToDatabaseCommand());
        $this->add(new SearchMissingSubtitlesCommand());
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }
}
