<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq\Producer;

use Bunny\Channel;
use Bunny\Exception\BunnyException;
use Exception;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\PipesPhpSdk\RabbitMq\BunnyManager;
use Hanaboso\PipesPhpSdk\RabbitMq\ContentTypes;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RabbitMqBundle\Consumer\DebugMessageTrait;

/**
 * Class AbstractProducer
 *
 * @package Hanaboso\PipesPhpSdk\RabbitMq\Producer
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
     * @param string|null  $beforeMethod
     * @param string       $contentType
     * @param BunnyManager $manager
     */
    public function __construct(
        string $exchange,
        string $routingKey,
        bool $mandatory,
        bool $immediate,
        ?string $beforeMethod,
        string $contentType,
        BunnyManager $manager
    )
    {
        $this->exchange     = $exchange;
        $this->routingKey   = $routingKey;
        $this->mandatory    = $mandatory;
        $this->immediate    = $immediate;
        $this->beforeMethod = $beforeMethod;
        $this->contentType  = $contentType;
        $this->manager      = $manager;

        $this->logger = new NullLogger();
    }

    /**
     * @param mixed $message
     *
     * @return mixed[]
     * @throws Exception
     */
    public function beforeSerializer($message): array
    {
        if (is_string($message)) {
            $message = Json::decode($message);
        }

        if ($this->getBeforeMethod()) {
            $this->{$this->beforeMethod}($message, $this->manager->getChannel());
        }

        return $message;
    }

    /**
     * @param mixed       $message
     * @param string|null $routingKey
     * @param mixed[]     $headers
     *
     * @return void
     * @throws Exception
     */
    public function publish($message, ?string $routingKey = NULL, array $headers = []): void
    {
        switch ($this->getContentType()) {
            case ContentTypes::APPLICATION_JSON:
                $message = Json::encode($this->beforeSerializer($message));
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
