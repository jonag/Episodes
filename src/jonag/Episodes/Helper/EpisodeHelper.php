<?php

namespace jonag\Episodes\Helper;


class EpisodeHelper
{
    const PATTERN = '/^(.+)[\.|\s]S?([0-9]+)[Ex]([0-9]+)(?:\-?[Ex]?[0-9]+)*?[\.|\s][^\[]+[^\.]([\.|\s]?\[.+\])?$/';

    private $showName;
    private $season;
    private $episode;
    private $proper;
    private $releaseName;

    /**
     * EpisodeHelper constructor.
     * @param string  $showName
     * @param int     $season
     * @param string  $episode
     * @param string  $releaseName
     * @param boolean $isProper
     */
    public function __construct($showName, $season, $episode, $releaseName, $isProper)
    {
        $this->showName = $showName;
        $this->season = $season;
        $this->episode = $episode;
        $this->releaseName = $releaseName;
        $this->proper = $isProper;
    }

    /**
     * @param string $fileName
     * @return bool|EpisodeHelper
     */
    public static function parseFileName($fileName)
    {
        if (preg_match(self::PATTERN, $fileName, $matches)) {
            $showName = str_replace('.', ' ', $matches[1]);
            $season = (int) $matches[2];
            $episode = $matches[3];
            $releaseName = isset($matches[4]) ? str_replace($matches[4], '', $matches[0]) : $matches[0];
            $isProper = stripos($releaseName, 'PROPER') !== false || stripos($releaseName, 'REPACK') !== false;

            return new EpisodeHelper($showName, $season, $episode, $releaseName, $isProper);
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getShowName()
    {
        return $this->showName;
    }

    /**
     * @return string
     */
    public function getSeason()
    {
        return $this->season;
    }

    /**
     * @return string
     */
    public function getEpisode()
    {
        return $this->episode;
    }

    /**
     * @return boolean
     */
    public function isProper()
    {
        return $this->proper;
    }

    /**
     * @return string
     */
    public function getReleaseName()
    {
        return $this->releaseName;
    }
}
