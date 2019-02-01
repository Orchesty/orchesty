<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 22.8.17
 * Time: 12:42
 */

namespace Hanaboso\PipesFramework\RabbitMq\Producer;

use Bunny\Channel;
use Bunny\Exception\BunnyException;
use Exception;
use Hanaboso\PipesFramework\HbPFRabbitMqBundle\ContentTypes;
use Hanaboso\PipesFramework\HbPFRabbitMqBundle\DebugMessageTrait;
use Hanaboso\PipesFramework\RabbitMq\BunnyManager;
use Hanaboso\PipesFramework\RabbitMq\Serializers\IMessageSerializer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class AbstractProducer
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Base
 */
class AbstractProducer implements LoggerAwareInterface
{

    use DebugMessageTrait;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $exchange;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @var boolean
     */
    private $mandatory;

    /**
     * @var boolean
     */
    private $immediate;

    /**
     * @var string|null
     */
    private $serializerClassName;

    /**
     * @var IMessageSerializer|null
     */
    private $serializer = NULL;

    /**
     * @var ?string
     */
    private $beforeMethod;

    /**
     * @var string
     */
    private $contentType;

    /**
     * @var BunnyManager
     */
    protected $manager;

    /**
     * AbstractProducer constructor.
     *
     * @param string       $exchange
     * @param string       $routingKey
     * @param bool         $mandatory
     * @param bool         $immediate
     * @param string       $serializerClassName
     * @param string|null  $beforeMethod
     * @param string       $contentType
     * @param BunnyManager $manager
     */
    public function __construct(
        string $exchange,
        string $routingKey,
        bool $mandatory,
        bool $immediate,
        ?string $serializerClassName,
        ?string $beforeMethod,
        string $contentType,
        BunnyManager $manager
    )
    {
        $this->exchange            = $exchange;
        $this->routingKey          = $routingKey;
        $this->mandatory           = $mandatory;
        $this->immediate           = $immediate;
        $this->serializerClassName = $serializerClassName;
        $this->beforeMethod        = $beforeMethod;
        $this->contentType         = $contentType;
        $this->manager             = $manager;

        $this->logger = new NullLogger();
    }

    /**
     * @return IMessageSerializer|null
     */
    public function createSerializer(): ?IMessageSerializer
    {
        if ($this->serializerClassName) {
            /** @var IMessageSerializer $metaClassName */
            $metaClassName = $this->serializerClassName;

            return $metaClassName::getInstance();
        } else {

            return NULL;
        }
    }

    /**
     * @return IMessageSerializer|null
     */
    public function getSerializer(): ?IMessageSerializer
    {
        if ($this->serializer === NULL) {
            $this->serializer = $this->createSerializer();
        }

        return $this->serializer;
    }

    /**
     * @param mixed $message
     *
     * @return array
     * @throws Exception
     */
    public function beforeSerializer($message): array
    {
        if (!$this->getSerializer()) {
            $this->getLogger()
                ->warning(
                    sprintf('Could not create meta class %s.', $this->serializerClassName),
                    $this->prepareMessage($message)
                );
            throw new BunnyException(
                sprintf('Could not create meta class %s.', $this->serializerClassName)
            );
        }

        if (is_string($message) && $this->serializer) {
            $message = $this->serializer->fromJson($message);
        }

        if ($this->getBeforeMethod()) {
            $this->{$this->beforeMethod}($message, $this->manager->getChannel());
        }

        return $message;
    }

    /**
     * @param mixed       $message
     * @param string|null $routingKey
     * @param array       $headers
     *
     * @return void
     * @throws Exception
     */
    public function publish($message, ?string $routingKey = NULL, array $headers = []): void
    {
        switch ($this->getContentType()) {
            case ContentTypes::APPLICATION_JSON:
                $message = $this->beforeSerializer($message);

                if ($this->serializer instanceof IMessageSerializer) {
                    $message = $this->serializer->toJson($message);
                } else {
                    throw new BunnyException('Cannot serialize message to JSON.');
                }
                break;
            case ContentTypes::TEXT_PLAIN:
                break;

            default:
                throw new BunnyException(
                    sprintf('Unhandled content type \'%s\'.', $this->contentType)
                );
        }

        if ($routingKey === NULL) {
            $routingKey = $this->routingKey;
        }

        $headers['content-type'] = $this->contentType;

        $this->getLogger()->debug(
            'publish',
            $this->prepareMessage('', $this->exchange, $routingKey, $headers)
        );

        /** @var Channel $channel */
        $channel = $this->manager->getChannel();
        $channel->publish(
            $message,
            $headers,
            $this->exchange,
            $routingKey,
            $this->mandatory,
            $this->immediate
        );
    }

    /**
     * @return string
     */
    public function getExchange(): string
    {
        return $this->exchange;
    }

    /**
     * @return string
     */
    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }

    /**
     * @return bool
     */
    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    /**
     * @return bool
     */
    public function isImmediate(): bool
    {
        return $this->immediate;
    }

    /**
     * @return string|null
     */
    public function getSerializerClassName(): ?string
    {
        return $this->serializerClassName;
    }

    /**
     * @return string|null
     */
    public function getBeforeMethod(): ?string
    {
        return $this->beforeMethod;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @return BunnyManager
     */
    public function getManager(): BunnyManager
    {
        return $this->manager;
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param string $exchange
     *
     * @return void
     */
    public function setExchange(string $exchange): void
    {
        $this->exchange = $exchange;
    }

}
