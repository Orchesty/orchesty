<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFRabbitMqBundle\Command;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Exception\BunnyException;
use Bunny\Exception\ClientException;
use Bunny\Message;
use Bunny\Protocol\MethodBasicQosOkFrame;
use Bunny\Protocol\MethodQueueBindOkFrame;
use Bunny\Protocol\MethodQueueDeclareOkFrame;
use Exception;
use Hanaboso\PipesFramework\HbPFRabbitMqBundle\ContentTypes;
use Hanaboso\PipesFramework\RabbitMq\BunnyManager;
use Hanaboso\PipesFramework\RabbitMq\Consumer\BaseSyncConsumerAbstract;
use Hanaboso\PipesFramework\RabbitMq\Exception\RabbitMqException;
use Hanaboso\PipesFramework\RabbitMq\Serializers\IMessageSerializer;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TypeError;

/**
 * Class ConsumerCommand
 *
 * @package Hanaboso\PipesFramework\HbPFRabbitMqBundle\Command
 */
class ConsumerCommand extends Command implements LoggerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var BunnyManager
     */
    protected $manager;

    /**
     * @var BaseSyncConsumerAbstract[]
     */
    protected $consumers;

    /**
     * @var int
     */
    protected $messages = 0;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ConsumerCommand constructor.
     *
     * @param ContainerInterface $container
     * @param BunnyManager       $manager
     * @param array              $consumers
     */
    public function __construct(ContainerInterface $container, BunnyManager $manager, array $consumers)
    {
        parent::__construct("rabbit-mq:consumer");
        $this->container = $container;
        $this->manager   = $manager;
        $this->consumers = $consumers;
        $this->logger    = new NullLogger();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setDescription("Starts given consumer.")
            ->addArgument("consumer-name", InputArgument::REQUIRED, "Name of consumer.")
            ->addArgument("consumer-parameters", InputArgument::IS_ARRAY, "Argv input to consumer.", []);
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @throws Exception
     */
    private function reconnect(): void
    {
        do {
            $wait = 2;
            sleep($wait);
            $this->logger->debug(sprintf('Waiting for %ss.', $wait));
            try {
                $this->manager->getClient(TRUE)->connect();
                $connect = TRUE;
                $this->logger->info('RabbitMQ is connected.');
            } catch (ClientException $e) {
                $connect = FALSE;
                $this->logger->error('RabbitMQ is not connected.', ['exception' => $e]);
            }

        } while (!$connect);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output;
        /** @var string $arg */
        $arg          = $input->getArgument("consumer-name");
        $consumerName = strtolower($arg);

        if (!isset($this->consumers[$consumerName])) {
            throw new InvalidArgumentException(sprintf('Consumer \'%s\' doesn\'t exists.', $consumerName));
        }

        $consumerArgv = (array) $input->getArgument("consumer-parameters");
        array_unshift($consumerArgv, $consumerName);

        try {
            $this->logger->debug('RabbitMQ setup.');
            $this->manager->setUp();
        } catch (ClientException $e) {
            $this->logger->error('RabbitMQ is not connected.', ['exception' => $e]);
            $this->reconnect();
            $this->manager->setUp();
        }

        $channel = $this->manager->getChannel();

        /** @var BaseSyncConsumerAbstract $consumer */
        $consumer    = $this->consumers[$consumerName];
        $maxMessages = PHP_INT_MAX;
        $maxSeconds  = PHP_INT_MAX;
        /** @var array<string|null> $calledSetUps */
        $calledSetUps = [];
        $tickMethod   = NULL;
        $tickSeconds  = NULL;

        $maxMessages = min($maxMessages, $consumer->getMaxMessages() ?? PHP_INT_MAX);
        $maxSeconds  = min($maxSeconds, $consumer->getMaxSeconds() ?? PHP_INT_MAX);

        if (empty($consumer->getQueue())) {
            $queueOk = $channel->queueDeclare("", FALSE, FALSE, TRUE);
            if (!($queueOk instanceof MethodQueueDeclareOkFrame)) {
                throw new BunnyException("Could not declare anonymous queue.");
            }

            $consumer->setQueue($queueOk->queue);

            $bindOk = $channel->queueBind($consumer->getQueue(), $consumer->getExchange(), $consumer->getRoutingKey());
            if (!($bindOk instanceof MethodQueueBindOkFrame)) {
                throw new BunnyException("Could not bind anonymous queue.");
            }
        }

        if ($consumer->getPrefetchSize() || $consumer->getPrefetchCount()) {
            $qosOk = $channel->qos($consumer->getPrefetchSize(), $consumer->getPrefetchCount());
            if (!($qosOk instanceof MethodBasicQosOkFrame)) {
                throw new BunnyException("Could not set prefetch-size/prefetch-count.");
            }
        }

        $serializer = NULL;
        if ($consumer->getSerializer()) {
            $metaClassName = (string) $consumer->getSerializer();

            if (!class_exists($metaClassName)) {
                throw new BunnyException(sprintf('Consumer meta class %s does not exist.', $metaClassName));
            }

            if (!method_exists($metaClassName, "getInstance")) {
                throw new BunnyException(sprintf('Method %s::getInstance() does not exist.', $metaClassName));
            }

            /** @var mixed $metaClassName */
            $serializer = $metaClassName::getInstance();
        }

        if ($consumer->getSetUpMethod() && !isset($calledSetUps[$consumer->getSetUpMethod()])) {
            if (!method_exists($consumer, (string) $consumer->getSetUpMethod())) {
                throw new BunnyException(
                    sprintf('Init method %s::%s does not exist', get_class($consumer), $consumer->getSetUpMethod())
                );
            }

            $consumer->{$consumer->getSetUpMethod()}($channel, $channel->getClient(), $consumerArgv);
            $calledSetUps[$consumer->getSetUpMethod()] = TRUE;
        }

        if ($consumer->getTickMethod()) {
            if (!$consumer->getTickSeconds()) {
                throw new BunnyException(
                    "If you specify 'tickMethod', you have to specify 'tickSeconds' - " . get_class($consumer) . "."
                );
            }

            if (!method_exists($consumer, (string) $consumer->getTickMethod())) {
                throw new BunnyException(
                    sprintf('Tick method %s::%s does not exist.', get_class($consumer), $consumer->getTickMethod())
                );
            }

            $tickMethod  = $consumer->getTickMethod();
            $tickSeconds = $consumer->getTickSeconds();
        }

        $channel->consume(
            function (Message $message, Channel $channel, Client $client) use ($consumer, $serializer): void {
                $this->handleMessage($consumer, $message, $channel, $client, $serializer);
            },
            $consumer->getQueue(),
            $consumer->getConsumerTag(),
            $consumer->isNoLocal(),
            $consumer->isNoAck(),
            $consumer->isExclusive(),
            $consumer->isNowait(),
            $consumer->getArguments()
        );

        $startTime = microtime(TRUE);

        while (microtime(TRUE) < $startTime + $maxSeconds && $this->messages < $maxMessages) {
            $channel->getClient()->run($tickSeconds ?? $maxSeconds);
            if ($tickMethod) {
                $consumer->{$tickMethod}($channel, $channel->getClient());
            }
        }
        $channel->getClient()->disconnect();

        return 0;
    }

    /**
     * @param BaseSyncConsumerAbstract $consumer
     * @param Message                  $message
     * @param Channel                  $channel
     * @param Client                   $client
     * @param mixed                    $serializer
     *
     * @return void
     * @throws RabbitMqException
     */
    public function handleMessage(
        BaseSyncConsumerAbstract $consumer,
        Message $message,
        Channel $channel,
        Client $client,
        $serializer = NULL
    ): void
    {
        $data = $message->content;
        if ($serializer) {
            switch ($message->getHeader("content-type")) {
                case ContentTypes::APPLICATION_JSON:
                    if ($serializer instanceof IMessageSerializer) {
                        try {
                            $data = $serializer->fromJson($data);
                        } catch (TypeError $e) {
                            throw new BunnyException('Bad input data format.');
                        }

                    } else {
                        throw new BunnyException('Meta class does not support JSON.');
                    }
                    break;

                default:
                    throw new BunnyException("Message does not have 'content-type' header, cannot deserialize data.");
            }
        }

        $consumer->handleMessage($data, $message, $channel, $client);

        if ($consumer->getMaxMessages() !== NULL && ++$this->messages >= $consumer->getMaxMessages()) {
            $client->stop();
        }
    }

}
