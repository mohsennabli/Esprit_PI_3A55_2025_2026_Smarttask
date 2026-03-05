<?php

namespace App\Command;

use App\Service\FormationReminderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:formation:reminders',
    description: 'Smart reminders: 3 days before, 24h before, and follow-up for absent.',
)]
class SendFormationRemindersCommand extends Command
{
    public function __construct(
        private readonly FormationReminderService $reminderService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->reminderService->runAll();

        $io->success([
            sprintf('Rappels 3 jours avant : %d email(s)', $result['reminders_3d']),
            sprintf('Rappels 24h avant : %d email(s)', $result['reminders_24h']),
            sprintf('Suivis absents : %d email(s)', $result['absent_follow_ups']),
        ]);

        return Command::SUCCESS;
    }
}
