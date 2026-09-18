final <?php

namespace App\Service\Message;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Publisher
{
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
