<?php

namespace jonag\Episodes\Command;

use jonag\Episodes\Helper\EpisodeHelper;
use jonag\OpenSubtitlesSDK\Client;
use jonag\OpenSubtitlesSDK\Exception\OpenSubtitlesException;
use jonag\OpenSubtitlesSDK\Helper\Hash;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;


class SearchSubtitlesCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('subtitles:search')
            ->setDescription('Search and download the subtitle for an episode')
            ->addArgument('file', InputArgument::REQUIRED, 'The path to the video file')
            ->addOption('override', 'o', InputOption::VALUE_NONE, 'Override existing subtitles');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getApplication()->getContainer();
        $io = new SymfonyStyle($input, $output);

        $filePath = $input->getArgument('file');
        $fileInfo = new \SplFileInfo($filePath);

        if ($fileInfo->isDir()) {
            $finder = new Finder();
            $finder->files()->name('/.+\.(mp4|avi|mkv)/')->ignoreDotFiles(true);

            $result = 1;
            foreach ($finder->in($fileInfo->getPathname()) as $file) {
                $result = $this->searchSubtitlesForFile($file, $io, $container['osClient'], $input->getOption('override'));
            }
        } else {
            $result = $this->searchSubtitlesForFile($fileInfo, $io, $container['osClient'], $input->getOption('override'));
        }

        return $result;
    }


    /**
     * @param array $subtitles
     * @param EpisodeHelper|false $episode
     * @return null|string
     */
    protected function findBestSubtitle($subtitles, $episode)
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

    /**
     * @param \SplFileInfo                                  $fileInfo
     * @param \Symfony\Component\Console\Style\SymfonyStyle $io
     * @param \jonag\OpenSubtitlesSDK\Client                $osClient
     * @param boolean                                       $override
     * @return int
     */
    protected function searchSubtitlesForFile(\SplFileInfo $fileInfo, SymfonyStyle $io, Client $osClient, $override)
    {
        if (!$fileInfo->isFile()) {
            $io->error(sprintf('The resource %s is not a file', $fileInfo->getPathname()));

            return 1;
        }

        $subtitlesPath = $fileInfo->getPath().DIRECTORY_SEPARATOR.$fileInfo->getBasename(
                $fileInfo->getExtension()
            ).'en.srt';
        if (!$override && file_exists($subtitlesPath)) {
            $io->warning('Subtitles already exist for this file. Use option --override to override');

            return 2;
        }

        $io->text(sprintf('Looking for subtitles for the file %s', $fileInfo->getFilename()));
        $progressBar = $io->createProgressBar(4);

        $progressBar->start();
        $hash = Hash::calculateHash($fileInfo->getPathname());
        $progressBar->advance();

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
            $subtitles = $osClient->getSubtitles('eng', $searchOptions);
            $progressBar->advance();
        } catch (\Exception $e) {
            $progressBar->finish();
            $io->error(sprintf('An error occured while calling the OpenSubtitles API %s', $e->getMessage()));

            return 4;
        }

        $link = $this->findBestSubtitle($subtitles, $episode);
        if ($link === null) {
            $progressBar->finish();
            $io->warning('Unable to find matching subtitles');

            return 3;
        }
        $progressBar->advance();

        $gzSubtitles = @file_get_contents($link);
        if ($gzSubtitles === false) {
            $progressBar->finish();
            $io->error('Unable to download the subtitles');

            return 5;
        }
        $data = gzinflate(substr($gzSubtitles, 10));
        $copied = @file_put_contents($subtitlesPath, $data);
        if ($copied === false) {
            $progressBar->finish();
            $io->error(sprintf('Unable to write the file %s', $subtitlesPath));

            return 6;
        }
        $progressBar->finish();


        $io->success(sprintf('Subtitles downloaded'));

        return 0;
    }
}
