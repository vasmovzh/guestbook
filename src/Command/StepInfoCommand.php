<?php

namespace App\Command;

use Psr\Cache\CacheItemInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Cache\CacheInterface;

final class StepInfoCommand extends Command
{
    protected static $defaultName        = 'app:step:info';
    protected static $defaultDescription = 'Displays a current step of the app';

    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $step = $this->cache->get('app.current_step', static function (CacheItemInterface $item) {
            $process = new Process(['git', 'tag', '-l', '--points-at', 'HEAD']);

            $process->mustRun();

            $item->expiresAfter(30);

            return $process->getOutput();
        });


        $output->write($step);

        return Command::SUCCESS;
    }
}
