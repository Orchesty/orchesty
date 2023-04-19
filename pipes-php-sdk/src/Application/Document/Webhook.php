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
     * @return self
     */
    public function setCreated(?DateTime $created): self
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
     * @return self
     */
    public function setName(?string $name): self
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
     * @return self
     */
    public function setUser(?string $user): self
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
     * @return self
     */
    public function setToken(?string $token): self
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
     * @return self
     */
    public function setNode(?string $node): self
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
     * @return self
     */
    public function setTopology(?string $topology): self
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
     * @return self
     */
    public function setApplication(?string $application): self
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
     * @return self
     */
    public function setWebhookId(?string $webhookId): self
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
     * @return self
     */
    public function setUnsubscribeFailed(?bool $unsubscribeFailed): self
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
            'created'         => $this->getCreated()?->format(DateTimeUtils::DATE_TIME),
            'id'              => $this->getId(),
            'name'            => $this->getName(),
            'node'            => $this->getNode(),
            'token'           => $this->getToken(),
            'topology'        => $this->getTopology(),
            'webhookId'       => $this->getWebhookId(),
            self::APPLICATION => $this->getApplication(),
            self::USER        => $this->getUser(),
        ];
    }

    /**
     * @param mixed[] $data
     *
     * @return self
     * @throws Exception
     */
    protected function fromArray(array $data): self
    {
        if (array_key_exists('id', $data))
            $this->setId($data['id']);
        if (array_key_exists(self::USER, $data))
            $this->setUser($data[self::USER]);
        if (array_key_exists(self::APPLICATION, $data))
            $this->setApplication($data[self::APPLICATION]);
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
