<?php

namespace jonag\Episodes\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AddToDatabaseCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('subtitles:db:add')
            ->setDescription('Add an episode to the database of missing subtitles')
            ->addArgument('file', InputArgument::REQUIRED, 'The path to the video file');
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

        $file = $input->getArgument('file');
        if (!is_file($file)) {
            $io->error(sprintf('The resource %s is not a file', $file));

            return 1;
        }

        try {
            $statement = $pdo->prepare('INSERT INTO subs (file) VALUES (:file)');
            $statement->bindParam('file', $file);
            $statement->execute();
        } catch (\Exception $e) {
            $io->error(sprintf('Erreur SQLite : %s', $e->getMessage()));

            return 1;
        }

        $io->success('File added to database');
    }

}
