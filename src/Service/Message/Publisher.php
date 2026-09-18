<?php

namespace App\Service\Message;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class Publisher
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
    }

    public function publish(string $message, string $routingKey = 'todo.created'): void
    {
        $connection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->user,
            $this->password
        );

        $channel = $connection->channel();

        // Declare durable exchange
        $channel->exchange_declare('todos.demo', 'direct', false, true, false);

        // Create AMQP message
        $msg = new AMQPMessage($message, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);

        // Publish
        $channel->basic_publish($msg, 'todos.demo', $routingKey);

        $channel->close();
        $connection->close();
    }
}
