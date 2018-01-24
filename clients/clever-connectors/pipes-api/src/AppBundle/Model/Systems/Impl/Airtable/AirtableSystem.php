<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Airtable;

use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventObject;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventSystemInterface;
use CleverConnectors\AppBundle\Model\CMEvents\Traits\CMEventSystemTrait;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Dto\ActionDto;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class AirtableSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Airtable
 */
class AirtableSystem implements AuthorizationInterface, CMEventSystemInterface
{

    use SystemTrait {
        toArray as parentToArray;
    }
    use AuthorizationTrait;
    use CMEventSystemTrait;

    public const BASE_URL     = 'https://api.airtable.com/v0/';
    public const TABLE_URL    = 'table-url';
    public const LAYOUT       = 'datalayout';
    public const TEMPLATE     = 'map_templates';
    public const TEMPLATE_IN  = 'template_in';
    public const TEMPLATE_OUT = 'template_out';
    public const LIST_ID      = 'list-id';
    public const DATA_SET     = 'data_set';
    public const VIEW         = 'view';
    public const LAST_SYNC    = 'last_sync';

    private const API_KEY    = 'api_key';
    private const LIMIT_TIME = 1;

    /**
     * AirtableSystem constructor.
     */
    public function __construct()
    {
        $this->addCMEvent(new CMEventObject('', SystemInstall::EVENT_CREATE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::UNSUBSCRIBE, SystemInstall::EVENT_UNSUBSCRIBE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::HARD_BOUNCE, SystemInstall::EVENT_HARD_BOUNCE, ''));

        $topologyName = TopologyNameUtils::getTopologyName(TopologyNameUtils::SYNC, $this->getKey());
        $this->addAllowedAction(new ActionDto($topologyName . '-in', MapTemplate::DIRECTION_IN));

        $topologyName = TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, $this->getKey());
        $this->addAllowedAction(new ActionDto($topologyName . '-in', MapTemplate::DIRECTION_IN));

        $topologyName = TopologyNameUtils::getTopologyName(TopologyNameUtils::CREATE_CONTACT, $this->getKey());
        $this->addAllowedAction(new ActionDto($topologyName . '-in', MapTemplate::DIRECTION_IN));

        $topologyName = TopologyNameUtils::getTopologyName(TopologyNameUtils::CREATE_CONTACT, $this->getKey());
        $this->addAllowedAction(new ActionDto($topologyName . '-out', MapTemplate::DIRECTION_OUT));

        $topologyName = TopologyNameUtils::getTopologyName(TopologyNameUtils::UNSUBSCRIBE_CONTACT, $this->getKey());
        $this->addAllowedAction(new ActionDto($topologyName . '-out', MapTemplate::DIRECTION_OUT));

        $topologyName = TopologyNameUtils::getTopologyName(TopologyNameUtils::HARD_BOUNCE_CONTACT, $this->getKey());
        $this->addAllowedAction(new ActionDto($topologyName . '-out', MapTemplate::DIRECTION_OUT));
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        $settings = $systemInstall->getSettings();

        return !empty($settings[self::API_KEY] ?? '');
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
        return SystemTypeEnum::CRON;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'airtable';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Airtable';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Airtable system';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'Logo';
    }

    /**
     * @return bool
     */
    public function isDynamicMapper(): bool
    {
        return TRUE;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $method
     * @param bool          $appendQuery
     *
     * @return RequestDto
     * @throws SystemException
     */
    public function getRequestDto(SystemInstall $systemInstall, string $method, $appendQuery = TRUE): RequestDto
    {
        $this->continueOnAuthorized($systemInstall);

        $settings = $systemInstall->getSettings();

        return (new RequestDto($method, new Uri(self::BASE_URL)))
            ->setHeaders([
                'Authorization' => sprintf('Bearer %s', $settings[self::API_KEY]),
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ]);
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    public function getSettingFields(SystemInstall $systemInstall): array
    {
        $settings = $systemInstall->getSettings();

        $field1 = new Field(
            Field::TEXT,
            self::API_KEY,
            'API Key',
            $this->prepareValue(self::API_KEY, $settings),
            TRUE
        );

        $field2 = new Field(
            Field::TEXT,
            SystemInstall::SYSTEM_LIMIT_VALUE,
            'System Limit',
            $this->prepareValue(SystemInstall::SYSTEM_LIMIT_VALUE, $settings),
            TRUE
        );

        $field4 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_CREATE,
            'Create event',
            $systemInstall->isEventCreate()
        );

        $field5 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_UNSUBSCRIBE,
            'UnSubscribe event',
            $systemInstall->isEventUnsubscribe()
        );

        $field6 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_HARD_BOUNCE,
            'Hard Bounce event',
            $systemInstall->isEventHardBounce()
        );

        $form = new Form();
        $form
            ->addField($field1)
            ->addField($field2)
            ->addField($field4)
            ->addField($field5)
            ->addField($field6);

        return $form->toArray();
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

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     */
    public function saveCustomForm(SystemInstall $systemInstall, array $data = []): array
    {
        $tables = [];

        foreach ($data as $index => $row) {
            if (in_array($row[self::TABLE_URL], $tables)) {
                unset($data[$index]);
            } else {
                $tables[] = $row[self::TABLE_URL];
            }
        }

        $this->setSettings($systemInstall, [SystemInstall::FORMS => $data]);

        $res                       = $this->parentToArray($systemInstall);
        $res[SystemInstall::FORMS] = $data;

        return $res;
    }

    /**
     * @param SystemInstall|null $systemInstall
     *
     * @return array
     */
    public function toArray(?SystemInstall $systemInstall = NULL): array
    {
        $arr = $this->parentToArray($systemInstall);
        if ($systemInstall && array_key_exists(SystemInstall::FORMS, $systemInstall->getSettings())) {
            $arr[SystemInstall::FORMS] = $systemInstall->getSettings()[SystemInstall::FORMS];
        }

        return $arr;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemLimitDto|null
     */
    public function getLimit(SystemInstall $systemInstall): ?SystemLimitDto
    {
        $limitValue = 5; // default value
        $settings   = $systemInstall->getSettings();

        if (array_key_exists(SystemInstall::SYSTEM_LIMIT_VALUE, $settings)) {
            $limitValue = $settings[SystemInstall::SYSTEM_LIMIT_VALUE];
        }

        return new SystemLimitDto(
            $systemInstall,
            SystemLimitDto::LIMIT_FOR_USER,
            self::LIMIT_TIME,
            $limitValue
        );
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

}