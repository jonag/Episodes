<?php

namespace jonag\Episodes\Command;

use jonag\OpenSubtitlesSDK\Client;
use jonag\OpenSubtitlesSDK\Exception\OpenSubtitlesException;
use jonag\OpenSubtitlesSDK\Helper\Hash;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class SearchAllSubtitlesCommand extends Command
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
        if (!$fileInfo->isFile()) {
            $io->error(sprintf('The resource %s is not a file', $filePath));

            return;
        }

        $subtitlesPath = $fileInfo->getPath().DIRECTORY_SEPARATOR.$fileInfo->getBasename(
                $fileInfo->getExtension()
            ).'en.srt';
        if (file_exists($subtitlesPath) && !$input->getOption('override')) {
            $io->warning('Subtitles already exist for this file. Use option --override to override');

            return;
        }

        $io->text(sprintf('Looking for subtitles for the file %s', $fileInfo->getFilename()));
        $progressBar = $io->createProgressBar(4);

        $progressBar->start();
        $hash = Hash::calculateHash($filePath);
        $progressBar->advance();

        /** @var Client $osClient */
        $osClient = $container['osClient'];
        try {
            $subtitles = $osClient->getSubtitles('en', $hash, filesize($filePath));
            $progressBar->advance();
        } catch (OpenSubtitlesException $e) {
            $progressBar->finish();
            $io->error(sprintf('An error occured while calling the OpenSubtitles API %s', $e->getMessage()));

            return;
        }

        $link = $this->findBestSubtitle($fileInfo->getFilename(), $subtitles);
        if ($link === null) {
            $progressBar->finish();
            $io->error('Unable to find matching subtitles');

            return;
        }
        $progressBar->advance();

        $gzSubtitles = @file_get_contents($link);
        if ($gzSubtitles === false) {
            $progressBar->finish();
            $io->error('Unable to download the subtitles');

            return;
        }
        $data = gzinflate(substr($gzSubtitles, 10));
        $copied = @file_put_contents($subtitlesPath, $data);
        if ($copied === false) {
            $progressBar->finish();
            $io->error(sprintf('Unable to write the file %s', $subtitlesPath));

            return;
        }
        $progressBar->finish();


        $io->success(sprintf('Subtitles downloaded'));

        return;
    }


    /**
     * @param $file
     * @param $subtitles
     * @return string|null
     */
    protected function findBestSubtitle($file, $subtitles)
    {
        foreach ($subtitles as $subtitle) {
            if ($subtitle['SubHearingImpaired'] === '0' && stripos($subtitle['MovieReleaseName'], $file) !== -1) {
                return $subtitle['SubDownloadLink'];
            }
        }

        return null;
    }
}
