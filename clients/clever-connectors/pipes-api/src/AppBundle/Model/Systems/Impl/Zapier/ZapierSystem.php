<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zapier;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Enum\SystemUITypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventObject;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventSystemInterface;
use CleverConnectors\AppBundle\Model\CMEvents\Traits\CMEventSystemTrait;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;

/**
 * Class ZapierSystem
 *
 * @package AppBundle\Model\Systems\Impl\Zapier
 */
class ZapierSystem implements WebhookSystemInterface, AuthorizationInterface, CMEventSystemInterface
{

    use SystemTrait;
    use WebhookSystemTrait;
    use AuthorizationTrait;
    use CMEventSystemTrait;

    public const CREATE_WEBHOOK_URL = 'create_webhook_url';
    public const UPDATE_WEBHOOK_URL = 'update_webhook_url';

    /**
     * ZapierSystem constructor.
     *
     * @throws CleverConnectorsException
     */
    public function __construct()
    {

        $this->addCMEvent(new CMEventObject('', SystemInstall::EVENT_CREATE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::UNSUBSCRIBE,
            SystemInstall::EVENT_UNSUBSCRIBE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::HARD_BOUNCE,
            SystemInstall::EVENT_HARD_BOUNCE, ''));

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

        $this->topologyNames['zapier-hard-bounce-subscriber'] = 'zapier-unsubscribe-subscriber';
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
    public function getUIType(): string
    {
        return SystemUITypeEnum::BASIC;
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
     * @return RequestDto
     */
    public function getRequestDto(SystemInstall $systemInstall, string $method): RequestDto
    {
        $dto = new RequestDto($method, new Uri());
        $dto->setHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ]);

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function getSettingFields(SystemInstall $systemInstall): array
    {
        $field1 = new Field(
            Field::TEXT,
            'user',
            'User',
            $systemInstall->getUser(),
            TRUE
        );

        $field2 = new Field(
            Field::TEXT,
            'token',
            'Token',
            $systemInstall->getToken(),
            TRUE
        );

        $field3 = new Field(
            Field::SELECT,
            SystemInstall::SELECT_LIST,
            'Distribution list',
            $this->prepareValue(SystemInstall::SELECT_LIST, $systemInstall->getSettings())
        );

        $field4 = new Field(
            Field::URL,
            self::CREATE_WEBHOOK_URL,
            'Create webhook URL',
            $this->prepareValue(self::CREATE_WEBHOOK_URL, $systemInstall->getSettings()),
            FALSE
        );

        $field5 = new Field(
            Field::URL,
            self::UPDATE_WEBHOOK_URL,
            'Update webhook URL',
            $this->prepareValue(self::UPDATE_WEBHOOK_URL, $systemInstall->getSettings()),
            FALSE
        );

        $field6 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_CREATE,
            'Create event',
            $systemInstall->isEventCreate()
        );

        $field7 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_UNSUBSCRIBE,
            'Unsubscribe event',
            $systemInstall->isEventUnsubscribe()
        );

        $field8 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_HARD_BOUNCE,
            'Hard bounce events',
            $systemInstall->isEventHardBounce()
        );

        $form = new Form();
        $form
            ->addField($field1)
            ->addField($field2)
            ->addField($field3)
            ->addField($field4)
            ->addField($field5)
            ->addField($field6)
            ->addField($field7)
            ->addField($field8);

        return $form->toArray();
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemLimitDto|null
     */
    public function getLimit(SystemInstall $systemInstall): ?SystemLimitDto
    {
        return NULL;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function saveLimit(SystemInstall $systemInstall, array $data): SystemInstall
    {
        return $systemInstall;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface
     * @throws SystemException
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
     * @return RequesterInterface
     * @throws SystemException
     */
    public function getUnsubscribeRequester(SystemInstall $systemInstall): RequesterInterface
    {
        throw new SystemException(
            'Method [getUnsubscribeRequest] not implemented in Zapier system.',
            SystemException::SYSTEM_METHOD_NOT_FOUND
        );
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface|null
     */
    public function getCMEventRequester(SystemInstall $systemInstall): ?RequesterInterface
    {
        return NULL;
    }

}