<?php

namespace App\Command;

use App\Repository\CommentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class CommentCleanupCommand extends Command
{
    protected static $defaultName        = 'app:comment:cleanup';
    protected static $defaultDescription = 'Cleans up old rejected or spam comments (older than 1 day)';

    private CommentRepository $commentRepository;

    public function __construct(CommentRepository $commentRepository)
    {
        parent::__construct();

        $this->commentRepository = $commentRepository;
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $count  = $dryRun ? $this->commentRepository->countOldRejectedOrSpam() : $this->commentRepository->deleteOldRejectedOrSpam();

        if ($dryRun) {
            $io->note('Dry mode enabled');
        }

        $io->success(sprintf('Deleted %d old rejected/spam comments', $count));

        return Command::SUCCESS;
    }
}
