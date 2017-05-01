<?php

namespace jonag\Episodes\Provider;

use jonag\Episodes\Helper\EpisodeHelper;
use jonag\OpenSubtitlesSDK\Client;
use jonag\OpenSubtitlesSDK\Helper\Hash;
use Symfony\Component\Console\Style\OutputStyle;

class OpenSubtitlesProvider implements ProviderInterface
{
    private $osClient;

    public function __construct(Client $osClient)
    {
        $this->osClient = $osClient;
    }

    /**
     * @param \Symfony\Component\Console\Style\OutputStyle $io
     * @param \SplFileInfo                                 $fileInfo
     * @return null|string
     */
    public function findSubtitleForFile(OutputStyle $io, \SplFileInfo $fileInfo)
    {
        $hash = Hash::calculateHash($fileInfo->getPathname());

        $searchOptions = [
            'hash' => [
                'movieHash' => $hash,
                'movieSize' => filesize($fileInfo->getPathname())
            ],
        ];

        $episode = EpisodeHelper::parseFileName($fileInfo->getBasename('.'.$fileInfo->getExtension()));
        if ($episode !== false) {
            $searchOptions['query'] = [
                'showName' => $episode->getShowName(),
                'season' => $episode->getSeason(),
                'episode' => $episode->getEpisode(),
            ];
        }

        try {
            $subtitles = $this->osClient->getSubtitles('eng', $searchOptions);
        } catch (\Exception $e) {
            $io->error(sprintf('An error occured while calling the OpenSubtitles API %s', $e->getMessage()));

            return null;
        }

        $link = $this->findBestSubtitle($subtitles, $episode);
        if ($link === null) {
            $io->warning('Unable to find matching subtitles');

            return null;
        }

        $gzSubtitles = @file_get_contents($link);
        if ($gzSubtitles === false) {
            $io->error('Unable to download the subtitles');

            return null;
        }

        return gzinflate(substr($gzSubtitles, 10));
    }

    /**
     * @param array $subtitles
     * @param EpisodeHelper|false $episode
     * @return null|string
     */
    private function findBestSubtitle($subtitles, $episode)
    {
        $bestScore = -1;
        $bestDownloadsCount = -1;
        $link = null;

        foreach ($subtitles as $subtitle) {
            if ($subtitle['SubHearingImpaired'] !== '0') {
                continue;
            }

            if ($episode !== false
                && $subtitle['MatchedBy'] === 'fulltext'
                && $episode->getTeam() !== null
                && strpos($subtitle['MovieReleaseName'], $episode->getTeam()) === false) {
                continue;
            }

            $score = 0;

            if ($subtitle['MatchedBy'] === 'moviehash') {
                $score += 10;
            }

            if ($subtitle['UserRank'] === 'trusted' || $subtitle['UserRank'] === 'administrator') {
                $score += 4;
            } elseif ($subtitle['UserRank'] === 'platinum member' || $subtitle['UserRank'] === 'gold member') {
                $score += 3;
            }

            if ($score > $bestScore || ($score === $bestScore && (int) $subtitle['SubDownloadsCnt'] > $bestDownloadsCount)) {
                $bestScore = $score;
                $bestDownloadsCount = (int) $subtitle['SubDownloadsCnt'];
                $link = $subtitle['SubDownloadLink'];
            }
        }

        return $link;
    }
}
