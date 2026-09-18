<?php

namespace App\Command;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAfinal mqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:worker-mq',
    description: 'Run the RabbitMQ worker',
)]
class WorkerCommand extends Command
{
    public function __construct(
        #[Autowire('%env(RABBITMQ_HOST)%')]
        private string $host,
        #[Autowire('%env(int:RABBITMQ_PORT)%')]
        private int $port,
        #[Autowire('%env(RABBITMQ_USER)%')]
        private string $user,
        #[Autowire('%env(RABBITMQ_PASSWORD)%')]
        private string $password,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $connection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->user,
            $this->password
        );
        $channel = $connection->channel();

        // Declare durable exchange
        // Parameters: name, type, passive, durable, nowait
        $channel->exchange_declare('todos.demo', 'direct', false, true, false);

        // Declare durable queue (persists across crashes and RabbitMQ restarts)
        // Parameters: name, passive, durable, exclusive, auto_delete, nowait
        $channel->queue_declare('queue', false, true, false, false);

        // Bind queue to exchange with routing key
        $channel->queue_bind('queue', 'todos.demo', 'todo.created');

        // QoS
        $channel->basic_qos(1, 1, null);

        $io->info('Worker listening for messages... Press Ctrl+C to stop.');

        $channel->basic_consume(
            'queue',
            'worker',
            false,
            false,
            false,
            false,
            function (AMQPMessage $msg) use ($io, $channel) {
                $io->writeln('Received: '.$msg->body);
                $channel->basic_ack($msg->delivery_info['delivery_tag']);
            }
        );

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

        return Command::SUCCESS;
    }
}
