<?php

namespace jonag\Episodes\Helper;


class EpisodeHelper
{
    const PATTERN = '/^(?<showName>.+)[\. ]S?(?<season>\d+)[Ex](?<episode>\d+).*?(?:-(?<team>\w+))?(?<source>\[\w+\])?$/i';

    private $showName;
    private $season;
    private $episode;
    private $proper;
    private $releaseName;
    private $sample;
    private $team;

    /**
     * EpisodeHelper constructor.
     * @param string  $showName
     * @param int     $season
     * @param string  $episode
     * @param string  $releaseName
     * @param boolean $isProper
     * @param boolean $isSample
     * @param string  $team
     */
    public function __construct($showName, $season, $episode, $releaseName, $isProper, $isSample, $team)
    {
        $this->showName = $showName;
        $this->season = $season;
        $this->episode = $episode;
        $this->releaseName = $releaseName;
        $this->proper = $isProper;
        $this->sample = $isSample;
        $this->team = $team;
    }

    /**
     * @param string $fileName
     * @return bool|EpisodeHelper
     */
    public static function parseFileName($fileName)
    {
        if (preg_match(self::PATTERN, $fileName, $matches)) {
            $showName = ucwords(strtolower(str_replace('.', ' ', $matches['showName'])));
            $season = (int) $matches['season'];
            $episode = $matches['episode'];
            $releaseName = isset($matches['source']) ? str_replace($matches['source'], '', $fileName) : $fileName;
            $team = isset($matches['team']) ? $matches['team'] : null;
            $isProper = stripos($releaseName, 'PROPER') !== false || stripos($releaseName, 'REPACK') !== false;
            $isSample = stripos($releaseName, 'SAMPLE') !== false;

            return new EpisodeHelper($showName, $season, $episode, $releaseName, $isProper, $isSample, $team);
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

    /**
     * @return boolean
     */
    public function isSample()
    {
        return $this->sample;
    }

    /**
     * @return string
     */
    public function getTeam()
    {
        return $this->team;
    }
}
