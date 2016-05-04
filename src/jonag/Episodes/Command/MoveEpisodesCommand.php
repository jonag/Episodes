<?php

namespace jonag\Episodes\Command;

use jonag\Episodes\Helper\EpisodeHelper;
use Psr\Log\AbstractLogger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class MoveEpisodesCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('episodes:move')
            ->addOption('source', 's', InputOption::VALUE_REQUIRED, 'The directory containing the downloaded files')
            ->addOption('target', 't', InputOption::VALUE_REQUIRED, 'The directory where the episodes are copied')
            ->setDescription('Move the episodes from the download directory to the destination directory');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getApplication()->getContainer();
        $config = $container['config'];
        $logger = new ConsoleLogger($output);

        $source = $input->getOption('source') ?: $config['source_directory'];
        if (!is_dir($source)) {
            $logger->error('The source must be a directory');
            return;
        }

        $target = $input->getOption('target') ?: $config['target_directory'];
        if (!is_dir($target)) {
            $logger->error('The target must be a directory');
            return;
        }

        $this->exploreDirectory($logger, $source, $target, $config['ignore_if_nuked'], $config['delete_nuked']);
    }

    /**
     * @param AbstractLogger $logger
     * @param string         $source
     * @param string         $target
     */
    protected function exploreDirectory(AbstractLogger $logger, $source, $target, $ignoreIfNuked, $deleteNuked)
    {
        $finder = new Finder();
        $finder->files()->name('/.+\.(mp4|avi|mkv)/')->ignoreDotFiles(true);

        $logger->debug(sprintf('Looking for files in directory %s', $source));
        foreach ($finder->in($source) as $file) {
            /** @var SplFileInfo $file $episode */
            $episode = EpisodeHelper::parseFileName($file->getBasename('.'.$file->getExtension()));

            if (!$episode) {
                $logger->debug(sprintf('File %s ignored because it\'s not an episode', $file->getBasename()));
                continue;
            }

            $directoryPath = $target.DIRECTORY_SEPARATOR.$episode->getShowName().DIRECTORY_SEPARATOR.'Saison '.$episode->getSeason();
            if (!file_exists($directoryPath)) {
                $logger->debug(sprintf('The directory %s does not exist', $directoryPath));
                if (!mkdir($directoryPath, 0777, true)) {
                    $logger->error('Error while creating the directory for the new show');
                    continue;
                }
                $logger->debug('Directory created');
            }

            if ($ignoreIfNuked && !$episode->isProper() && $this->properExists($directoryPath, $episode->getSeason(), $episode->getEpisode())) {
                $logger->debug(sprintf('The release %s is nuked, ignoring it', $episode->getReleaseName()));
                continue;
            }

            $filePath = $directoryPath.DIRECTORY_SEPARATOR.$episode->getReleaseName().'.'.$file->getExtension();
            if (file_exists($filePath) === true) {
                $logger->debug(sprintf('The file %s already exists', $filePath));
                continue;
            }

            $logger->notice(sprintf('Starting the copy of the file %s', $file->getFilename()));
            $success = copy($file->getPathname(), $filePath);
            if (!$success) {
                $logger->error('An error occured while copying the file');
                continue;
            }

            $logger->notice(sprintf('File copied with the name %s', $episode->getReleaseName().'.'.$file->getExtension()));

            if ($episode->isProper() && $deleteNuked) {
                $this->deleteNuked($logger, $directoryPath, $episode->getSeason(), $episode->getEpisode());
            }
        }
    }

    /**
     * @param string $directoryPath
     * @param int    $season
     * @param int    $episode
     * @return bool
     */
    protected function properExists($directoryPath, $season, $episode)
    {
        $finder = new Finder();
        $finder->files()->name('/^(.+)\.S?0?'.$season.'[Ex]'.$episode.'.+(PROPER|REPACK).+\.(mp4|avi|mkv)/')->ignoreDotFiles(true);

        return $finder->in($directoryPath)->count() > 0;
    }

    /**
     * @param AbstractLogger $logger
     * @param $directoryPath
     * @param $season
     * @param $episode
     */
    private function deleteNuked(AbstractLogger $logger, $directoryPath, $season, $episode)
    {
        $logger->notice('Looking for nuked release...');
        $finder = new Finder();
        $finder->files()->name('/^(.+)\.S?0?'.$season.'[Ex]'.$episode.'.+\.(mp4|avi|mkv)/')->ignoreDotFiles(true);

        foreach ($finder->in($directoryPath) as $file) {
            /** @var SplFileInfo $file */
            if (stripos($file->getFilename(), 'PROPER') === false) {
                $logger->notice(sprintf('Deleting nuked release %s', $file->getFilename()));
                unlink($file);
            }
        }
    }
}
