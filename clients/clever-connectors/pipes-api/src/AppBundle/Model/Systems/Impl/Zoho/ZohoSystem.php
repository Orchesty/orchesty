<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho;

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
use DateTime;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;

/**
 * Class ZohoSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho
 */
class ZohoSystem implements AuthorizationInterface, CMEventSystemInterface
{

    use SystemTrait;
    use AuthorizationTrait;
    use CMEventSystemTrait;

    public const AUTH_TOKEN        = 'auth_token';
    public const LIMIT_STATUS_CODE = 4820;
    public const AUTH_STATUS_CODES = [4834, 4600, 4001, 401];

    private const SYSTEM_PLAN          = 'system_plan';
    private const SYSTEM_USER_LICENCES = 'system_user_licences';

    private const PLAN_STANDARD     = 'standard';
    private const PLAN_PROFESSIONAL = 'professional';
    private const PLAN_ENTERPRISE   = 'enterprise';
    private const PLAN_ULTIMATE     = 'ultimate';

    private const PLANS = [
        self::PLAN_STANDARD     => 'STANDARD',
        self::PLAN_PROFESSIONAL => 'PROFESSIONAL',
        self::PLAN_ENTERPRISE   => 'ENTERPRISE',
        self::PLAN_ULTIMATE     => 'ULTIMATE',
    ];

    /**
     * ZohoSystem constructor.
     *
     * @throws CleverConnectorsException
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
    public function getUIType(): string
    {
        return SystemUITypeEnum::BASIC;
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
     * @throws CurlException
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
     * @throws CleverConnectorsException
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

        $field5 = new Field(
            Field::SELECT,
            SystemInstall::SELECT_LIST,
            'Distribution list',
            $this->prepareValue(SystemInstall::SELECT_LIST, $systemInstall->getSettings())
        );

        $field6 = (new Field(
            Field::SELECT,
            self::SYSTEM_PLAN,
            'Plan',
            $this->prepareValue(self::SYSTEM_PLAN, $systemInstall->getSettings()),
            TRUE
        ))->setChoices(self::PLANS);

        $field7 = new Field(
            Field::NUMBER,
            self::SYSTEM_USER_LICENCES,
            'User licences',
            $this->prepareValue(self::SYSTEM_USER_LICENCES, $systemInstall->getSettings())
        );

        $form = new Form();
        $form
            ->addField($field1)
            ->addField($field2)
            ->addField($field3)
            ->addField($field4)
            ->addField($field5)
            ->addField($field6)
            ->addField($field7);

        return $form->toArray();
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemLimitDto|null
     */
    public function getLimit(SystemInstall $systemInstall): ?SystemLimitDto
    {
        $licences = $this->prepareValue(self::SYSTEM_USER_LICENCES, $systemInstall->getSettings()) ?? 1;

        switch ($this->prepareValue(self::SYSTEM_PLAN, $systemInstall->getSettings())) {
            case self::PLAN_STANDARD:
                return new SystemLimitDto(
                    $systemInstall,
                    SystemLimitDto::LIMIT_FOR_USER,
                    86400,
                    max(2000, min(250 * $licences, 5000)),
                    new DateTime()
                );

            case self::PLAN_PROFESSIONAL:
                return new SystemLimitDto(
                    $systemInstall,
                    SystemLimitDto::LIMIT_FOR_USER,
                    86400,
                    max(3000, min(250 * $licences, 10000)),
                    new DateTime()
                );

            case self::PLAN_ENTERPRISE:
            case self::PLAN_ULTIMATE:
                return new SystemLimitDto(
                    $systemInstall,
                    SystemLimitDto::LIMIT_FOR_USER,
                    86400,
                    max(4000, min(500 * $licences, 25000)),
                    new DateTime()
                );

            default:
                return new SystemLimitDto(
                    $systemInstall,
                    SystemLimitDto::LIMIT_FOR_USER,
                    86400,
                    1000,
                    new DateTime()
                );
        }
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