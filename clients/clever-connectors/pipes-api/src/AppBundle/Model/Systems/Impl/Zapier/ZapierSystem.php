<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zapier;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class ZapierSystem
 *
 * @package AppBundle\Model\Systems\Impl\Zapier
 */
class ZapierSystem implements WebhookSystemInterface, AuthorizationInterface
{

    use WebhookSystemTrait;
    use AuthorizationTrait;

    /**
     * ZapierSystem constructor.
     *
     */
    public function __construct()
    {
        $this->subscriptions[] = new WebhookSubscribes(
            'zapier-created-subscriber-connector',
            TopologyNameUtils::getTopologyName(TopologyNameUtils::CREATED_SUBSCRIBERS, $this->getKey())
        );
        $this->subscriptions[] = new WebhookSubscribes(
            'zapier-updated-subscriber-connector',
            TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, $this->getKey())
        );
        $this->subscriptions[] = new WebhookSubscribes(
            'zapier-deleted-subscriber-connector',
            TopologyNameUtils::getTopologyName(TopologyNameUtils::DELETED_SUBSCRIBERS, $this->getKey())
        );
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return SystemTypeEnum::UI_WEBHOOK;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'zapier';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Zapier';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Zapier system';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'Logo';
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        return TRUE;
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return self::BASIC;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $method
     *
     * @return RequestDto|void
     * @throws SystemException
     */
    public function getRequestDto(SystemInstall $systemInstall, string $method): RequestDto
    {
        throw new SystemException(
            'Method [getRequestDto] not implemented in Zapier system.',
            SystemException::SYSTEM_METHOD_NOT_FOUND
        );
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    public function getSettingFields(SystemInstall $systemInstall): array
    {
        $field1 = new Field(
            Field::TEXT,
            'user',
            'User',
            $systemInstall->getUser(),
            FALSE,
            TRUE
        );

        $field2 = new Field(
            Field::TEXT,
            'token',
            'Token',
            $systemInstall->getToken(),
            FALSE,
            TRUE
        );

        return (new Form())
            ->addField($field1)
            ->addField($field2)
            ->toArray();

    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface|void
     * @throws SystemException
     *
     */
    public function getSubscribeRequester(
        SystemInstall $systemInstall
    ): RequesterInterface
    {
        throw new SystemException(
            'Method [getSubscribeRequest] not implemented in Zapier system.',
            SystemException::SYSTEM_METHOD_NOT_FOUND
        );
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface|void
     * @throws SystemException
     *
     */
    public function getUnsubscribeRequester(SystemInstall $systemInstall): RequesterInterface
    {
        throw new SystemException(
            'Method [getUnsubscribeRequest] not implemented in Zapier system.',
            SystemException::SYSTEM_METHOD_NOT_FOUND
        );
    }

}