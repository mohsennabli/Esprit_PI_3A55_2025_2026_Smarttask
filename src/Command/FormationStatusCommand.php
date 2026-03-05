<?php

namespace App\Command;

use App\Service\FormationStatusService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:formation:status',
    description: 'Automatic status management: close past formations, mark non-validated inscriptions as absent.',
)]
class FormationStatusCommand extends Command
{
    public function __construct(
        private readonly FormationStatusService $statusService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->statusService->runAll();

        $io->success([
            sprintf('Formations marquées terminées : %d', $result['formations_closed']),
            sprintf('Inscriptions marquées absentes : %d', $result['inscriptions_marked_absent']),
        ]);

        return Command::SUCCESS;
    }
}
