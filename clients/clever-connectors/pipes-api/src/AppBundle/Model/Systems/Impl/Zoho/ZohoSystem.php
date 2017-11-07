<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventObject;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventSystemInterface;
use CleverConnectors\AppBundle\Model\CMEvents\Traits\CMEventSystemTrait;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class ZohoSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho
 */
class ZohoSystem implements SystemInterface, AuthorizationInterface, CMEventSystemInterface
{

    use AuthorizationTrait;
    use CMEventSystemTrait;

    public const AUTH_TOKEN = 'auth_token';

    /**
     * ZohoSystem constructor.
     */
    public function __construct()
    {
        $this->addCMEvent(new CMEventObject('', SystemInstall::EVENT_CREATE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::UNSUBSCRIBE, SystemInstall::EVENT_UNSUBSCRIBE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::HARD_BOUNCE, SystemInstall::EVENT_HARD_BOUNCE, ''));

        $this->topologyNames['zoho-unsubscribe-contact'] = 'zoho-update-contact';
        $this->topologyNames['zoho-hard-bounce-contact'] = 'zoho-update-contact';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return SystemTypeEnum::CRON;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'zoho';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'ZOHO';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'ZOHO';
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
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        return !empty($systemInstall->getSettings()[self::AUTH_TOKEN]);
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
        $this->continueOnAuthorized($systemInstall);

        $sett = $systemInstall->getSettings();
        $dto  = new RequestDto('GET', new Uri(sprintf(
            'https://crm.zoho.eu/crm/private/json/Contacts/%%s?authtoken=%s&scope=crmapi',
            $sett[self::AUTH_TOKEN]
        )));
        $dto->setHeaders($this->getHeaders());

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
            self::AUTH_TOKEN,
            'Authorization token',
            $this->prepareValue(self::AUTH_TOKEN, $systemInstall->getSettings()),
            TRUE
        );

        $field2 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_CREATE,
            'Create event',
            $systemInstall->isEventCreate()
        );

        $field3 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_UNSUBSCRIBE,
            'UnSubscribe event',
            $systemInstall->isEventUnsubscribe()
        );

        $field4 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_HARD_BOUNCE,
            'Hard Bounce event',
            $systemInstall->isEventHardBounce()
        );

        $form = new Form();
        $form
            ->addField($field1)
            ->addField($field2)
            ->addField($field3)
            ->addField($field4);

        return $form->toArray();
    }

    /**
     * --------------------------------------------- HELPERS ---------------------------------------------
     */

    /**
     * @return array
     */
    private function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
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