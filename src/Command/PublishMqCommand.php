<?php

namespace App\Command;

use App\Service\Message\Publisher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:publish-mq',
    description: 'Publish a message to RabbitMQ',
)]
class PublishMqCommand extends Command
{
    public function __construct(private readonly Publisher $publisher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('message', InputArgument::REQUIRED, 'Message to publish');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $message = $input->getArgument('message');

        try {
            $this->publisher->publish($message);
            $io->success("Message published: $message");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Failed to publish: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
