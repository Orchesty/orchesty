<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Mailmunch;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use CleverConnectors\AppBundle\Utils\WebhookUtils;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;

/**
 * Class MailmunchSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Mailmunch
 */
class MailmunchSystem implements WebhookSystemInterface, AuthorizationInterface
{

    use AuthorizationTrait;
    use WebhookSystemTrait;
    /**
     * @var string
     */
    private $domain;

    /**
     * MailmunchSystem constructor.
     *
     * @param string $domain
     */
    function __construct(string $domain)
    {
        $this->subscriptions[] = new WebhookSubscribes(
            'mailmunch-create-email-connector',
            'mailmunch-create-email',
            '',
            '');
        $this->domain          = $domain;
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
        return 'mailmunch';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Mailmunch';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Mailmunch system';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'logo';
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
        throw new SystemException('Method [getRequestDto] not implemented in Mailmunch system.',
            SystemException::SYSTEM_METHOD_NOT_FOUND);
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    public function getSettingFields(SystemInstall $systemInstall): array
    {
        $field1 = new Field(
            Field::URL,
            'webhook_url',
            'Webhook url',
            WebhookUtils::getWebhookUrl(
                $this->domain,
                $systemInstall->getUser(),
                $systemInstall->getToken(),
                'mailmunch-create-email-connector',
                'mailmunch-create-email'
            ),
            FALSE,
            TRUE
        );

        $form = new Form();
        $form->addField($field1);

        return $form->toArray();
    }

    /**
     * @param WebhookSubscribes $subscription
     * @param SystemInstall     $systemInstall
     * @param string            $url
     *
     * @return RequestDto|void
     * @throws SystemException
     */
    public function getSubscribeRequest(
        WebhookSubscribes $subscription,
        SystemInstall $systemInstall,
        string $url
    ): RequestDto
    {
        throw new SystemException('Method [getSubscribeRequest] not implemented in Mailmunch system.',
            SystemException::SYSTEM_METHOD_NOT_FOUND);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $webhookId
     *
     * @return RequestDto|void
     * @throws SystemException
     */
    public function getUnsubscribeRequest(SystemInstall $systemInstall, string $webhookId): RequestDto
    {
        throw new SystemException('Method [getUnsubscribeRequest] not implemented in Mailmunch system.',
            SystemException::SYSTEM_METHOD_NOT_FOUND);
    }

    /**
     * @param ResponseDto $response
     *
     * @return string|void
     * @throws SystemException
     */
    public function getWebhookId(ResponseDto $response): string
    {
        throw new SystemException('Method [getWebhookId] not implemented in Mailmunch system.',
            SystemException::SYSTEM_METHOD_NOT_FOUND);
    }

}