<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Traits\Document\CreatedTrait;
use Hanaboso\CommonsBundle\Traits\Document\IdTrait;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;

/**
 * Class Webhook
 *
 * @package Hanaboso\PipesFramework\Application\Document
 *
 * @ODM\Document()
 */
class Webhook
{

    public const USER        = 'user';
    public const APPLICATION = 'application';

    use IdTrait;
    use CreatedTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $user;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $token;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $node;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $topology;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $application;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $webhookId;

    /**
     * @var bool
     *
     * @ODM\Field(type="boolean")
     */
    private $unsubscribeFailed = FALSE;

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
