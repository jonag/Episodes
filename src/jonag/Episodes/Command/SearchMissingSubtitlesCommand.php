<?php

namespace jonag\Episodes\Command;


use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class SearchMissingSubtitlesCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('subtitles:missing')
            ->setDescription('Search and download missing subtitless');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $container = $this->getApplication()->getContainer();
        /** @var \PDO $pdo */
        $pdo = $container['pdo'];

        try {
            $query = $pdo->query("SELECT * FROM subs");
            $missingSubtitles = $query->fetchAll();
        } catch (\Exception $e) {
            $io->error(sprintf('Erreur SQLite : %s', $e->getMessage()));

            return 1;
        }

        $searchSubtitlesCommand = $this->getApplication()->find('subtitles:search');

        foreach ($missingSubtitles as $missingSubtitle) {
            $arguments = [
                'command' => 'subtitles:search',
                'file' => $missingSubtitle['file'],
                'override' => true,
            ];
            $commandInput = new ArrayInput($arguments);
            $subtitlesFound = $searchSubtitlesCommand->run($commandInput, $io);

            if ($subtitlesFound === 0) {
                $statement = $pdo->prepare('DELETE FROM subs WHERE id = :id');
                $statement->bindParam('id', $missingSubtitle['id'], \PDO::PARAM_INT);
                try {
                    $statement->execute();
                } catch (\Exception $e) {
                    $io->error(sprintf('Erreur SQLite : %s', $e->getMessage()));

                    return 1;
                }
            }
        }

        return 0;
    }
}
