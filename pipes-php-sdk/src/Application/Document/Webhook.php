<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Document;

use DateTime;
use Exception;
use Hanaboso\PipesPhpSdk\Storage\Mongodb\DocumentAbstract;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class Webhook
 *
 * @package Hanaboso\PipesPhpSdk\Application\Document
 */
class Webhook extends DocumentAbstract
{

    public const USER        = 'user';
    public const APPLICATION = 'application';

    /**
     * @var DateTime|null
     */
    protected ?DateTime $created = NULL;

    /**
     * @var string|null
     */
    private ?string $name = NULL;

    /**
     * @var string|null
     */
    private ?string $user = NULL;

    /**
     * @var string|null
     */
    private ?string $token = NULL;

    /**
     * @var string|null
     */
    private ?string $node = NULL;

    /**
     * @var string|null
     */
    private ?string $topology = NULL;

    /**
     * @var string|null
     */
    private ?string $application = NULL;

    /**
     * @var string|null
     */
    private ?string $webhookId = NULL;

    /**
     * @var bool
     */
    private bool $unsubscribeFailed = FALSE;

    /**
     * Webhook constructor.
     *
     * @param mixed[]|null $data
     *
     * @throws DateTimeException
     */
    public function __construct(?array $data = [])
    {
        $this->created = DateTimeUtils::getUtcDateTime();

        parent::__construct($data);
    }

    /**
     * @return DateTime|null
     */
    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime|null $created
     *
     * @return $this
     */
    public function setCreated(?DateTime $created): Webhook
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     *
     * @return $this
     */
    public function setName(?string $name): Webhook
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->user;
    }

    /**
     * @param string|null $user
     *
     * @return $this
     */
    public function setUser(?string $user): Webhook
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @param string|null $token
     *
     * @return $this
     */
    public function setToken(?string $token): Webhook
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNode(): ?string
    {
        return $this->node;
    }

    /**
     * @param string|null $node
     *
     * @return $this
     */
    public function setNode(?string $node): Webhook
    {
        $this->node = $node;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTopology(): ?string
    {
        return $this->topology;
    }

    /**
     * @param string|null $topology
     *
     * @return $this
     */
    public function setTopology(?string $topology): Webhook
    {
        $this->topology = $topology;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getApplication(): ?string
    {
        return $this->application;
    }

    /**
     * @param string|null $application
     *
     * @return $this
     */
    public function setApplication(?string $application): Webhook
    {
        $this->application = $application;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getWebhookId(): ?string
    {
        return $this->webhookId;
    }

    /**
     * @param string|null $webhookId
     *
     * @return $this
     */
    public function setWebhookId(?string $webhookId): Webhook
    {
        $this->webhookId = $webhookId;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUnsubscribeFailed(): bool
    {
        return $this->unsubscribeFailed;
    }

    /**
     * @param bool|null $unsubscribeFailed
     *
     * @return $this
     */
    public function setUnsubscribeFailed(?bool $unsubscribeFailed): Webhook
    {
        $this->unsubscribeFailed = $unsubscribeFailed ?? FALSE;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'id'                 => $this->getId(),
            Webhook::USER        => $this->getUser(),
            Webhook::APPLICATION => $this->getApplication(),
            'created'            => $this->getCreated()?->format(DateTimeUtils::DATE_TIME),
            'name'               => $this->getName(),
            'webhookId'          => $this->getWebhookId(),
            'node'               => $this->getNode(),
            'token'              => $this->getToken(),
            'topology'           => $this->getTopology(),
        ];
    }

    /**
     * @param mixed[] $data
     *
     * @return Webhook
     * @throws Exception
     */
    protected function fromArray(array $data): Webhook
    {
        if (array_key_exists('id', $data))
            $this->setId($data['id']);
        if (array_key_exists(Webhook::USER, $data))
            $this->setUser($data[Webhook::USER]);
        if (array_key_exists(Webhook::APPLICATION, $data))
            $this->setApplication($data[Webhook::APPLICATION]);
        if (array_key_exists('created', $data))
            $this->setCreated($data['created'] ? new DateTime($data['created']) : NULL);
        if (array_key_exists('name', $data))
            $this->setName($data['name']);
        if (array_key_exists('webhookId', $data))
            $this->setWebhookId($data['webhookId']);
        if (array_key_exists('node', $data))
            $this->setNode($data['node']);
        if (array_key_exists('token', $data))
            $this->setToken($data['token']);
        if (array_key_exists('topology', $data))
            $this->setTopology($data['topology']);
        if (array_key_exists('unsubscribeFailed', $data))
            $this->setUnsubscribeFailed($data['unsubscribeFailed']);

        return $this;
    }

}
