<?php

namespace jonag\Episodes;

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
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }
}