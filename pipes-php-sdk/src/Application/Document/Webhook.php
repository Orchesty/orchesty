<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\CreatedTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class Webhook
 *
 * @package Hanaboso\PipesPhpSdk\Application\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesPhpSdk\Application\Repository\WebhookRepository")
 */
class Webhook
{

    use IdTrait;
    use CreatedTrait;

    public const USER        = 'user';
    public const APPLICATION = 'application';

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $name;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $user;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $token;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $node;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $topology;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $application;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $webhookId;

    /**
     * @var bool
     *
     * @ODM\Field(type="boolean")
     */
    private bool $unsubscribeFailed = FALSE;

    /**
     * Webhook constructor.
     *
     * @throws DateTimeException
     */
    public function __construct()
    {
        $this->created = DateTimeUtils::getUtcDateTime();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Webhook
     */
    public function setName(string $name): Webhook
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     *
     * @return Webhook
     */
    public function setUser(string $user): Webhook
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return Webhook
     */
    public function setToken(string $token): Webhook
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getNode(): string
    {
        return $this->node;
    }

    /**
     * @param string $node
     *
     * @return Webhook
     */
    public function setNode(string $node): Webhook
    {
        $this->node = $node;

        return $this;
    }

    /**
     * @return string
     */
    public function getTopology(): string
    {
        return $this->topology;
    }

    /**
     * @param string $topology
     *
     * @return Webhook
     */
    public function setTopology(string $topology): Webhook
    {
        $this->topology = $topology;

        return $this;
    }

    /**
     * @return string
     */
    public function getApplication(): string
    {
        return $this->application;
    }

    /**
     * @param string $application
     *
     * @return Webhook
     */
    public function setApplication(string $application): Webhook
    {
        $this->application = $application;

        return $this;
    }

    /**
     * @return string
     */
    public function getWebhookId(): string
    {
        return $this->webhookId;
    }

    /**
     * @param string $webhookId
     *
     * @return Webhook
     */
    public function setWebhookId(string $webhookId): Webhook
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
     * @param bool $unsubscribeFailed
     *
     * @return Webhook
     */
    public function setUnsubscribeFailed(bool $unsubscribeFailed): Webhook
    {
        $this->unsubscribeFailed = $unsubscribeFailed;

        return $this;
    }

}
