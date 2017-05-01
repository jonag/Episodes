<?php

namespace jonag\Episodes\Command;

use jonag\Episodes\Provider\ProviderInterface;
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
                $result = $this->searchSubtitlesForFile($file, $io, $container['provider'], $input->getOption('override'));
            }
        } else {
            $result = $this->searchSubtitlesForFile($fileInfo, $io, $container['provider'], $input->getOption('override'));
        }

        return $result;
    }

    /**
     * @param \SplFileInfo                                                                              $fileInfo
     * @param \Symfony\Component\Console\Style\SymfonyStyle                                             $io
     * @param \jonag\Episodes\Provider\ProviderInterface $provider
     * @param boolean                                                                                   $override
     * @return int
     */
    protected function searchSubtitlesForFile(\SplFileInfo $fileInfo, SymfonyStyle $io, ProviderInterface $provider, $override)
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

            return 0;
        }

        $io->text(sprintf('Looking for subtitles for the file %s', $fileInfo->getFilename()));

        if ($subtitles = $provider->findSubtitleForFile($io, $fileInfo) === null) {
            return 1;
        }

        $copied = @file_put_contents($subtitlesPath, $subtitles);
        if ($copied === false) {
            $io->error(sprintf('Unable to write the file %s', $subtitlesPath));

            return 1;
        }

        $io->success(sprintf('Subtitles downloaded'));

        return 0;
    }
}
