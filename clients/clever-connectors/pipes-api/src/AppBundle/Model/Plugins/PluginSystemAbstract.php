<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\PluginHeadersEnum;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventObject;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventSystemInterface;
use CleverConnectors\AppBundle\Model\CMEvents\Traits\CMEventSystemTrait;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Plugins\Requester\SwitchTokenRequester;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class PluginSystemAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Plugins
 */
abstract class PluginSystemAbstract implements AuthorizationInterface, CMEventSystemInterface
{

    use AuthorizationTrait;
    use CMEventSystemTrait;

    protected const SWITCH_TOKEN               = 'clever_connector/switch_token';
    protected const CREATE_SUBSCRIBER_URL      = 'clever_connector/subscriber/create';
    protected const UNSUBSCRIBE_SUBSCRIBER_URL = 'clever_connector/subscriber/unsubscribe?id=%s';
    protected const HARD_BOUNCE_SUBSCRIBER_URL = 'clever_connector/subscriber/hard_bounce?id=%s';
    protected const SYNC_URL                   = 'clever_connector/subscriber?page=%s&limit=%s';
    protected const LIMIT_PER_PAGE             = 50;

    /**
     * PluginSystemAbstract constructor.
     */
    public function __construct()
    {
        $this->addCMEvent(new CMEventObject('', SystemInstall::EVENT_CREATE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::UNSUBSCRIBE, SystemInstall::EVENT_UNSUBSCRIBE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::HARD_BOUNCE, SystemInstall::EVENT_HARD_BOUNCE, ''));

        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::CREATED_SUBSCRIBERS,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::CREATED_SUBSCRIBERS, 'plugins');
        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, 'plugins');
        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::SYNC,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::SYNC, 'plugins');
        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::CREATE_CONTACT,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::CREATE_CONTACT, 'plugins');
        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::UNSUBSCRIBE_CONTACT,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::UNSUBSCRIBE_CONTACT, 'plugins');
        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::HARD_BOUNCE_CONTACT,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::HARD_BOUNCE_CONTACT, 'plugins');
        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::SWITCH_TOKEN,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::SWITCH_TOKEN, 'plugins');
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

        $form = new Form();
        $form
            ->addField($field1)
            ->addField($field2)
            ->addField($field3);

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
    public function getUsubscribeSubscriberUrl(): string
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
    public function getSyncUrl(): string
    {
        return static::SYNC_URL;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return static::LIMIT_PER_PAGE;
    }

    /**
     * @return string
     */
    public function getSwitchTokenUrl(): string
    {
        return self::SWITCH_TOKEN;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface
     */
    public function getSwitchTokenRequester(SystemInstall $systemInstall): RequesterInterface
    {
        return new SwitchTokenRequester($this->getRequestDto($systemInstall, CurlManager::METHOD_POST));
    }

}