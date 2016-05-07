<?php

namespace jonag\Episodes\Command;

use jonag\Episodes\Helper\EpisodeHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
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
        $io = new SymfonyStyle($input, $output);

        $source = $input->getOption('source') ?: $config['source_directory'];
        if (!is_dir($source)) {
            $io->error('The source must be a directory');

            return 1;
        }

        $target = $input->getOption('target') ?: $config['target_directory'];
        if (!is_dir($target)) {
            $io->error('The target must be a directory');

            return 1;
        }

        $this->exploreDirectory(
            $io,
            $source,
            $target,
            $config['ignore_if_nuked'],
            $config['delete_nuked'],
            $config['search_subtitles']
        );

        return 0;
    }

    /**
     * @param \Symfony\Component\Console\Style\OutputStyle $io
     * @param string                                       $source
     * @param string                                       $target
     * @param boolean                                      $ignoreIfNuked
     * @param boolean                                      $deleteNuked
     * @param boolean                                      $searchSubtitles
     */
    protected function exploreDirectory(
        OutputStyle $io,
        $source,
        $target,
        $ignoreIfNuked,
        $deleteNuked,
        $searchSubtitles
    )
    {
        $finder = new Finder();
        $finder->files()->name('/.+\.(mp4|avi|mkv)/')->ignoreDotFiles(true);

        $io->title(sprintf('Looking for files in directory %s', $source));
        foreach ($finder->in($source) as $file) {
            /** @var SplFileInfo $file $episode */
            $episode = EpisodeHelper::parseFileName($file->getBasename('.'.$file->getExtension()));

            if (!$episode) {
                $io->note(sprintf('File %s ignored because it\'s not an episode', $file->getBasename()));
                continue;
            }

            $io->section(sprintf('Handling file %s', $file->getBasename()));
            $directoryPath = $target.DIRECTORY_SEPARATOR.$episode->getShowName().DIRECTORY_SEPARATOR.'Saison '.$episode->getSeason();
            if (!file_exists($directoryPath)) {
                $io->text(sprintf('The directory %s does not exist', $directoryPath));
                if (!mkdir($directoryPath, 0777, true)) {
                    $io->warning('Error while creating the directory for the new show');
                    continue;
                }
                $io->text('Directory created');
            }

            if ($ignoreIfNuked && !$episode->isProper() && $this->properExists($directoryPath, $episode->getSeason(), $episode->getEpisode())) {
                $io->note(sprintf('The release %s is nuked, ignoring it', $episode->getReleaseName()));
                continue;
            }

            $filePath = $directoryPath.DIRECTORY_SEPARATOR.$episode->getReleaseName().'.'.$file->getExtension();
            if (file_exists($filePath) === true) {
                $io->note(sprintf('The file %s already exists', $filePath));
                continue;
            }

            $io->text(sprintf('Starting the copy of the file %s', $file->getFilename()));
            $success = copy($file->getPathname(), $filePath);
            if (!$success) {
                $io->warning('An error occurred while copying the file');
                continue;
            }

            $io->success(sprintf('File copied with the name %s', $episode->getReleaseName().'.'.$file->getExtension()));

            if ($episode->isProper() && $deleteNuked) {
                $this->deleteNuked($io, $directoryPath, $episode->getSeason(), $episode->getEpisode());
            }

            if ($searchSubtitles) {
                $searchSubtitlesCommand = $this->getApplication()->find('subtitles:search');

                $arguments = [
                    'command' => 'subtitles:search',
                    'file' => $filePath,
                ];
                $commandInput = new ArrayInput($arguments);
                $subtitlesFound = $searchSubtitlesCommand->run($commandInput, $io);

                if ($subtitlesFound !== 0) {
                    $addToDatabaseCommand = $this->getApplication()->find('subtitles:db:add');

                    $arguments = [
                        'command' => 'subtitles:db:add',
                        'file' => $filePath,
                    ];
                    $commandInput = new ArrayInput($arguments);
                    $addToDatabaseCommand->run($commandInput, $io);
                }
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
     * @param \Symfony\Component\Console\Style\OutputStyle $io
     * @param                                              $directoryPath
     * @param                                              $season
     * @param                                              $episode
     * @internal param \Psr\Log\AbstractLogger $logger
     */
    private function deleteNuked(OutputStyle $io, $directoryPath, $season, $episode)
    {
        $io->text('Looking for nuked release...');
        $finder = new Finder();
        $finder->files()->name('/^(.+)\.S?0?'.$season.'[Ex]'.$episode.'.+\.(mp4|avi|mkv)/')->ignoreDotFiles(true);

        foreach ($finder->in($directoryPath) as $file) {
            /** @var SplFileInfo $file */
            if (stripos($file->getFilename(), 'PROPER') === false ||  stripos($file->getFilename(), 'REPACK') === false) {
                $io->text(sprintf('Deleting nuked release %s', $file->getFilename()));
                unlink($file);
            }
        }
    }
}
