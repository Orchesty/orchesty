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
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class ZapierSystem
 *
 * @package AppBundle\Model\Systems\Impl\Zapier
 */
class ZapierSystem implements WebhookSystemInterface, AuthorizationInterface
{

    use SystemTrait;
    use WebhookSystemTrait;
    use AuthorizationTrait;

    public const CREATE_WEBHOOK_URL = 'create_webhook_url';
    public const UPDATE_WEBHOOK_URL = 'update_webhook_url';

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
     * @return RequestDto
     * @throws SystemException
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

        $form = new Form();
        $form
            ->addField($field1)
            ->addField($field2)
            ->addField($field3)
            ->addField($field4)
            ->addField($field5);

        return $form->toArray();
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface
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
     * @return RequesterInterface
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