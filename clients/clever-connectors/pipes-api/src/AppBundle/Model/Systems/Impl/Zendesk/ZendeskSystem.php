<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
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
use CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Requester\ZendeskCmEventRequester;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use DateTime;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class ZendeskSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk
 */
class ZendeskSystem implements AuthorizationInterface, CMEventSystemInterface
{

    use SystemTrait;
    use AuthorizationTrait;
    use WebhookSystemTrait;
    use CMEventSystemTrait;

    public const DOMAIN = 'domain';

    private const USER      = 'user_email';
    private const API_TOKEN = 'api_token';

    private const BASE_URL          = 'https://%s.zendesk.com/';
    private const CUSTOM_FIELDS_URL = 'https://%s.zendesk.com/api/v2/user_fields.json';

    private const RATE_LIMIT = 'X-Rate-Limit';
    private const LIMIT_TIME = 60;

    /**
     * ZendeskSystem constructor.
     */
    function __construct()
    {
        $this->addCMEvent(new CMEventObject('', SystemInstall::EVENT_CREATE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::UNSUBSCRIBE,
            SystemInstall::EVENT_UNSUBSCRIBE, self::CUSTOM_FIELDS_URL));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::HARD_BOUNCE,
            SystemInstall::EVENT_HARD_BOUNCE, self::CUSTOM_FIELDS_URL));

        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::HARD_BOUNCE_CONTACT,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::UNSUBSCRIBE_CONTACT,
            $this->getKey());
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
        return 'zendesk';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Zendesk';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Zendesk';
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
        return !empty($systemInstall->getSettings()[self::API_TOKEN] ?? '')
            && !empty($systemInstall->getSettings()[self::USER] ?? '')
            && !empty($systemInstall->getSettings()[self::DOMAIN] ?? '');
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
        $dto  = new RequestDto($method, new Uri(sprintf(self::BASE_URL, $sett[self::DOMAIN])));
        $dto->setHeaders($this->getHeaders($systemInstall));

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    public function getSettingFields(SystemInstall $systemInstall): array
    {
        $sett = $systemInstall->getSettings();

        $field1 = new Field(
            Field::TEXT,
            self::USER,
            'User email',
            $this->prepareValue(self::USER, $sett),
            TRUE
        );

        $field2 = new Field(
            Field::TEXT,
            self::API_TOKEN,
            'Api token',
            $this->prepareValue(self::API_TOKEN, $sett),
            TRUE
        );

        $field3 = (new Field(
            Field::TEXT,
            self::DOMAIN,
            'Domain',
            $this->prepareValue(self::DOMAIN, $sett),
            TRUE
        ))->setDescription('Domain (XXX part in https://XXX.zendesk.com)');

        $field4 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_CREATE,
            'Create event',
            $systemInstall->isEventCreate()
        );

        $field5 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_UNSUBSCRIBE,
            'Unsubscribe event',
            $systemInstall->isEventUnsubscribe()
        );

        $field6 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_HARD_BOUNCE,
            'Hard bounce events',
            $systemInstall->isEventHardBounce()
        );

        $field7 = new Field(
            Field::SELECT,
            SystemInstall::SELECT_LIST,
            'Distribution list',
            $this->prepareValue(SystemInstall::SELECT_LIST, $sett)
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
     * @return RequesterInterface|null
     */
    public function getCMEventRequester(SystemInstall $systemInstall): ?RequesterInterface
    {
        return new ZendeskCmEventRequester($systemInstall, $this->getHeaders($systemInstall));
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemLimitDto|null
     */
    public function getLimit(SystemInstall $systemInstall): ?SystemLimitDto
    {
        $settings = $systemInstall->getSettings();
        if (array_key_exists(SystemInstall::SYSTEM_LIMITS, $settings)) {
            $systemLimits = $settings[SystemInstall::SYSTEM_LIMITS];

            if (array_key_exists(SystemInstall::SYSTEM_LIMIT_VALUE, $systemLimits)) {
                return new SystemLimitDto(
                    $systemInstall,
                    SystemLimitDto::LIMIT_FOR_USER,
                    self::LIMIT_TIME,
                    $systemLimits[SystemInstall::SYSTEM_LIMIT_VALUE],
                    $systemLimits[SystemInstall::SYSTEM_LIMIT_UPDATE] ?? NULL
                );
            }
        }

        return NULL;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     * @throws CleverConnectorsException
     */
    public function saveLimit(SystemInstall $systemInstall, array $data): SystemInstall
    {
        if (array_key_exists(self::RATE_LIMIT, $data) && isset($data[self::RATE_LIMIT][0])) {
            $this->setSettings($systemInstall, [
                SystemInstall::SYSTEM_LIMITS => [
                    SystemInstall::SYSTEM_LIMIT_VALUE  => $data[self::RATE_LIMIT][0],
                    SystemInstall::SYSTEM_LIMIT_UPDATE => new DateTime(),
                ],
            ]);

            return $systemInstall;
        }

        throw new CleverConnectorsException(
            sprintf('Missing %s value in response headers', self::RATE_LIMIT),
            CleverConnectorsException::MISSING_DATA
        );
    }

    /**
     * -------------------------------------------- HELPERS --------------------------------------------
     */

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    private function getHeaders(SystemInstall $systemInstall): array
    {
        $sett  = $systemInstall->getSettings();
        $token = base64_encode(sprintf('%s/token:%s', $sett[self::USER], $sett[self::API_TOKEN]));

        return [
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => sprintf('Basic %s', $token),
        ];
    }

}