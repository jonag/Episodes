<?php

namespace jonag\Episodes\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitDatabaseCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('subtitles:db:init')
            ->setDescription('Init the database used to store missing subtitles');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $container = $this->getApplication()->getContainer();
        $pdo = $container['pdo'];

        try {
            $pdo->query("CREATE TABLE IF NOT EXISTS subs (
                id  INTEGER PRIMARY KEY AUTOINCREMENT,
                file    VARCHAR(500)
            )");
        } catch (\Exception $e) {
            $io->error(sprintf('Erreur SQLite : %s', $e->getMessage()));

            return 1;
        }

        $io->success('Database initiated');
    }
}
