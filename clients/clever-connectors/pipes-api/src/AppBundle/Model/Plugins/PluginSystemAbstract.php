<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\PluginHeadersEnum;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Enum\SystemUITypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventObject;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventSystemInterface;
use CleverConnectors\AppBundle\Model\CMEvents\Traits\CMEventSystemTrait;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Plugins\Requester\SwitchTokenRequester;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;

/**
 * Class PluginSystemAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Plugins
 */
abstract class PluginSystemAbstract implements AuthorizationInterface, CMEventSystemInterface
{

    use SystemTrait;
    use AuthorizationTrait;
    use CMEventSystemTrait;

    protected const SWITCH_TOKEN               = 'clever_connector/switch_token';
    protected const CREATE_SUBSCRIBER_URL      = 'clever_connector/subscriber/create';
    protected const UNSUBSCRIBE_SUBSCRIBER_URL = 'clever_connector/subscriber/unsubscribe?id=%s';
    protected const HARD_BOUNCE_SUBSCRIBER_URL = 'clever_connector/subscriber/hard_bounce?id=%s';
    protected const SUBSCRIBE_SUBSCRIBER_URL   = 'clever_connector/subscriber/subscribe?id=%s';
    protected const SYNC_URL                   = 'clever_connector/subscriber?page=%s&limit=%s';
    protected const LIMIT_PER_PAGE             = 50;

    /**
     * PluginSystemAbstract constructor.
     *
     * @throws CleverConnectorsException
     */
    public function __construct()
    {
        $this->addCMEvent(new CMEventObject('', SystemInstall::EVENT_CREATE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::UNSUBSCRIBE, SystemInstall::EVENT_UNSUBSCRIBE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::HARD_BOUNCE, SystemInstall::EVENT_HARD_BOUNCE, ''));
        $this->addCMEvent(new CMEventObject('', SystemInstall::EVENT_SUBSCRIBE, ''));

        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::CREATED_SUBSCRIBERS,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::CREATED_SUBSCRIBERS, 'plugins');
        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, 'plugins');
        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::DELETED_SUBSCRIBERS,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::DELETED_SUBSCRIBERS, 'plugins');
        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::VALIDATE_SUBSCRIBERS,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::VALIDATE_SUBSCRIBERS, 'plugins');
        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::SYNC,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::SYNC, 'plugins');
        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::CREATE_CONTACT,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::CREATE_CONTACT, 'plugins');
        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::UNSUBSCRIBE_CONTACT,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::UNSUBSCRIBE_CONTACT, 'plugins');
        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::HARD_BOUNCE_CONTACT,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::HARD_BOUNCE_CONTACT, 'plugins');
        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::SUBSCRIBE_CONTACT,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::SUBSCRIBE_CONTACT, 'plugins');
        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::SWITCH_TOKEN,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::SWITCH_TOKEN, 'plugins');
    }

    /**
     * @return string
     */
    public function getUIType(): string
    {
        return SystemUITypeEnum::BASIC;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        return !empty($systemInstall->getSettings()[SystemInstall::SYSTEM_URL] ?? '')
            && !empty($systemInstall->getToken())
            && !empty($systemInstall->getUser());
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
     *
     * @return RequesterInterface|null
     */
    public function getCMEventRequester(SystemInstall $systemInstall): ?RequesterInterface
    {
        return NULL;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return SystemTypeEnum::PLUGIN;
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

        $dto = new RequestDto($method, new Uri());
        $dto->setHeaders([
            'Content-Type'           => 'application/json',
            PluginHeadersEnum::GUID  => $systemInstall->getUser(),
            PluginHeadersEnum::TOKEN => $systemInstall->getToken(),
        ]);

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $append
     *
     * @return Uri
     */
    public function createUri(SystemInstall $systemInstall, string $append): Uri
    {
        return new Uri(
            sprintf(
                '%s/%s',
                rtrim($systemInstall->getSettings()[SystemInstall::SYSTEM_URL], '/'),
                ltrim($append, '/')
            )
        );
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
            Field::CHECKBOX,
            SystemInstall::EVENT_CREATE,
            'Create event',
            $systemInstall->isEventCreate()
        );

        $field2 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_UNSUBSCRIBE,
            'Unsubscribe event',
            $systemInstall->isEventUnsubscribe()
        );

        $field3 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_HARD_BOUNCE,
            'Hard bounce event',
            $systemInstall->isEventHardBounce()
        );

        $field4 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_SUBSCRIBE,
            'Subscribe event',
            $systemInstall->isEventSubscribe()
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
     * @return string
     */
    public function getCreateSubscriberUrl(): string
    {
        return static::CREATE_SUBSCRIBER_URL;
    }

    /**
     * @return string
     */
    public function getUnsubscribeSubscriberUrl(): string
    {
        return static::UNSUBSCRIBE_SUBSCRIBER_URL;
    }

    /**
     * @return string
     */
    public function getHardBounceSubscriberUrl(): string
    {
        return static::HARD_BOUNCE_SUBSCRIBER_URL;
    }

    /**
     * @return string
     */
    public function getSubscribeSubscriberUrl(): string
    {
        return static::SUBSCRIBE_SUBSCRIBER_URL;
    }

    /**
     * @return string
     */
    public function getSyncUrl(): string
    {
        return static::SYNC_URL;
    }

    /**
     * @return int
     */
    public function getPageLimit(): int
    {
        return static::LIMIT_PER_PAGE;
    }

    /**
     * @return string
     */
    public function getSwitchTokenUrl(): string
    {
        return static::SWITCH_TOKEN;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface
     * @throws CurlException
     * @throws SystemException
     */
    public function getSwitchTokenRequester(SystemInstall $systemInstall): RequesterInterface
    {
        return new SwitchTokenRequester($this->getRequestDto($systemInstall, CurlManager::METHOD_POST));
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

}