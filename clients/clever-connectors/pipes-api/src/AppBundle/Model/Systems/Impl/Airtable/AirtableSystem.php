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

    use SystemTrait;
    use AuthorizationTrait;
    use CMEventSystemTrait;

    public const  BASE_URL = 'https://api.airtable.com/v0/';
    private const API_KEY  = 'api_key';
    private const VIEW     = 'view';
    private const URL      = 'url';

    /**
     * AirtableSystem constructor.
     */
    public function __construct()
    {
        $this->addCMEvent(new CMEventObject('', SystemInstall::EVENT_CREATE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::UNSUBSCRIBE, SystemInstall::EVENT_UNSUBSCRIBE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::HARD_BOUNCE, SystemInstall::EVENT_HARD_BOUNCE, ''));

        $topologyName = TopologyNameUtils::getTopologyName(TopologyNameUtils::SYNC, $this->getKey());
        $this->addAllowedAction(new ActionDto($topologyName, MapTemplate::DIRECTION_IN));

        $topologyName = TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, $this->getKey());
        $this->addAllowedAction(new ActionDto($topologyName, MapTemplate::DIRECTION_IN));

        $topologyName = TopologyNameUtils::getTopologyName(TopologyNameUtils::CREATE_CONTACT, $this->getKey());
        $this->addAllowedAction(new ActionDto($topologyName . '-in', MapTemplate::DIRECTION_IN));

        $topologyName = TopologyNameUtils::getTopologyName(TopologyNameUtils::CREATE_CONTACT, $this->getKey());
        $this->addAllowedAction(new ActionDto($topologyName . '-out', MapTemplate::DIRECTION_OUT));

        $topologyName = TopologyNameUtils::getTopologyName(TopologyNameUtils::UNSUBSCRIBE_CONTACT, $this->getKey());
        $this->addAllowedAction(new ActionDto($topologyName, MapTemplate::DIRECTION_OUT));

        $topologyName = TopologyNameUtils::getTopologyName(TopologyNameUtils::HARD_BOUNCE_CONTACT, $this->getKey());
        $this->addAllowedAction(new ActionDto($topologyName, MapTemplate::DIRECTION_OUT));
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        $settings = $systemInstall->getSettings();

        return !empty($settings[self::API_KEY] ?? '') && !empty($settings[self::URL] ?? '');
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

        $uri = $settings[self::URL];
        if (strpos($uri, '?')) {
            $tmp = explode('?', $uri);
            $uri = $tmp[0];
        }

        // use view if set
        if ($settings[self::VIEW] ?? '') {
            $uri .= sprintf('?view=%s', $settings[self::VIEW]);
        }

        return (new RequestDto($method, new Uri($uri)))
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
            self::URL,
            'Url of the table',
            $this->prepareValue(self::URL, $settings),
            TRUE
        );

        $field3 = new Field(
            Field::TEXT,
            self::VIEW,
            'View',
            $this->prepareValue(self::VIEW, $settings)
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
            ->addField($field3)
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

}