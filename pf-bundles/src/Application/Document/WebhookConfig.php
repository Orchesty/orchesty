<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\CreatedTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\UpdatedTrait;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class WebhookConfig
 *
 * @package Hanaboso\PipesFramework\Application\Document
 *
 * Stores the user-defined intent of a webhook subscription created in the
 * topology editor. It is kept separate from {@see Webhook}, which represents
 * the live registration with the external API (token, external `webhookId`).
 *
 * Keying on `topologyName` + `nodeName` + `application` is intentional — those
 * names survive topology version bumps, so existing external registrations do
 * not need to be re-created when the topology is re-saved.
 */
#[ODM\Document(repositoryClass: 'Hanaboso\PipesFramework\Application\Repository\WebhookConfigRepository')]
#[ODM\Index(
    name: 'UK_webhook_config_topology_node',
    keys: ['topologyName' => 'asc', 'nodeName' => 'asc', 'application' => 'asc', 'user' => 'asc', 'sdk' => 'asc'],
    options: ['unique' => TRUE],
)]
#[ODM\Index(name: 'IK_webhook_config_topology', keys: ['topologyName' => 'asc'])]
#[ODM\Index(name: 'IK_webhook_config_application', keys: ['application' => 'asc', 'user' => 'asc'])]
class WebhookConfig
{

    use CreatedTrait;
    use IdTrait;
    use UpdatedTrait;

    #[ODM\Field(type: 'string')]
    private string $topologyName;

    #[ODM\Field(type: 'string')]
    private string $nodeName;

    #[ODM\Field(type: 'string')]
    private string $application;

    #[ODM\Field(type: 'string')]
    private string $user;

    #[ODM\Field(type: 'string')]
    private string $sdk = '';

    /**
     * Webhook event identifier (matches `WebhookSubscription.name` in the SDK).
     */
    #[ODM\Field(type: 'string')]
    private string $eventName;

    /**
     * @var array<string, string>
     */
    #[ODM\Field(type: 'hash')]
    private array $parameters = [];

    /**
     * Whether the subscription should be active. The `Webhook` document is
     * created once an actual subscribe call to the external API succeeds.
     */
    #[ODM\Field(type: 'bool')]
    private bool $enabled = FALSE;

    /**
     * WebhookConfig constructor.
     *
     * @throws DateTimeException
     */
    public function __construct()
    {
        $this->created = DateTimeUtils::getUtcDateTime();
        $this->updated = DateTimeUtils::getUtcDateTime();
    }

    /**
     * @return string
     */
    public function getTopologyName(): string
    {
        return $this->topologyName;
    }

    /**
     * @param string $topologyName
     *
     * @return WebhookConfig
     */
    public function setTopologyName(string $topologyName): self
    {
        $this->topologyName = $topologyName;

        return $this;
    }

    /**
     * @return string
     */
    public function getNodeName(): string
    {
        return $this->nodeName;
    }

    /**
     * @param string $nodeName
     *
     * @return WebhookConfig
     */
    public function setNodeName(string $nodeName): self
    {
        $this->nodeName = $nodeName;

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
     * @return WebhookConfig
     */
    public function setApplication(string $application): self
    {
        $this->application = $application;

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
     * @return WebhookConfig
     */
    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getSdk(): string
    {
        return $this->sdk;
    }

    /**
     * @param string $sdk
     *
     * @return WebhookConfig
     */
    public function setSdk(string $sdk): self
    {
        $this->sdk = $sdk;

        return $this;
    }

    /**
     * @return string
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }

    /**
     * @param string $eventName
     *
     * @return WebhookConfig
     */
    public function setEventName(string $eventName): self
    {
        $this->eventName = $eventName;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array<string, string> $parameters
     *
     * @return WebhookConfig
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return WebhookConfig
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'application'  => $this->application,
            'created'      => $this->created->format(DATE_ATOM),
            'enabled'      => $this->enabled,
            'eventName'    => $this->eventName,
            'id'           => $this->getId(),
            'nodeName'     => $this->nodeName,
            'parameters'   => $this->parameters,
            'sdk'          => $this->sdk,
            'topologyName' => $this->topologyName,
            'updated'      => $this->updated->format(DATE_ATOM),
            'user'         => $this->user,
        ];
    }

}
