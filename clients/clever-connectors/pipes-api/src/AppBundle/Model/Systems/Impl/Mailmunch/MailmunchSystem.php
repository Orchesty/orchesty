<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Mailmunch;

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
use CleverConnectors\AppBundle\Utils\WebhookUtils;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

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
        $this->domain          = $domain;
        $this->subscriptions[] = new WebhookSubscribes(
            'mailmunch-created-email-connector',
            TopologyNameUtils::getTopologyName(TopologyNameUtils::CREATED_SUBSCRIBERS, $this->getKey())
        );
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
        return 'Mailmunch';
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
                $systemInstall,
                'mailmunch-created-email-connector',
                TopologyNameUtils::getTopologyName(TopologyNameUtils::CREATED_SUBSCRIBERS, $this->getKey())
            )
        );
        $field1->setReadOnly(TRUE);

        $form = new Form();
        $form->addField($field1);

        return $form->toArray();
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
     * @return RequesterInterface|void
     * @throws SystemException
     */
    public function getSubscribeRequester(SystemInstall $systemInstall): RequesterInterface
    {
        throw new SystemException('Method [getSubscribeRequester] not implemented in Mailmunch system.',
            SystemException::SYSTEM_METHOD_NOT_FOUND);
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface|void
     * @throws SystemException
     */
    public function getUnsubscribeRequester(SystemInstall $systemInstall): RequesterInterface
    {
        throw new SystemException('Method [getUnsubscribeRequester] not implemented in Mailmunch system.',
            SystemException::SYSTEM_METHOD_NOT_FOUND);
    }

}