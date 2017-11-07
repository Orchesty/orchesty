<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive;

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
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Requester\PipedriveCMEventRequester;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Requester\PipedriveSubscribeRequester;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Requester\PipedriveUnsubscribeRequester;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class PipedriveSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive
 */
class PipedriveSystem implements WebhookSystemInterface, AuthorizationInterface, CMEventSystemInterface
{

    use AuthorizationTrait;
    use WebhookSystemTrait;
    use CMEventSystemTrait;

    public const API_TOKEN = 'api_token';

    private const CUSTOM_FIELDS_URL = 'https://api.pipedrive.com/v1/personFields?api_token=%s';
    private const BASE_URL          = 'https://api.pipedrive.com/v1/';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * PipedriveSystem constructor.
     *
     * @param DocumentManager $dm
     */
    function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;

        $this->subscriptions[] = new WebhookSubscribes(
            'pipedrive-updated-person-connector',
            TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, $this->getKey())
        );
        $this->subscriptions[] = new WebhookSubscribes(
            'pipedrive-deleted-person-connector',
            TopologyNameUtils::getTopologyName(TopologyNameUtils::DELETED_SUBSCRIBERS, $this->getKey())
        );

        $this->addCMEvent(new CMEventObject('', SystemInstall::EVENT_CREATE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::UNSUBSCRIBE,
            SystemInstall::EVENT_UNSUBSCRIBE, self::CUSTOM_FIELDS_URL));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::HARD_BOUNCE,
            SystemInstall::EVENT_HARD_BOUNCE, self::CUSTOM_FIELDS_URL));

        $this->topologyNames['pipedrive-hard-bounce-contact'] = 'pipedrive-unsubscribe-contact';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return SystemTypeEnum::WEBHOOK;
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
    public function getKey(): string
    {
        return 'pipedrive';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Pipedrive';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Pipedrive';
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
        return !empty($systemInstall->getSettings()[self::API_TOKEN] ?? '');
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $method
     *
     * @return RequestDto
     * @throws SystemException
     */
    public function getRequestDto(SystemInstall $systemInstall, string $method = 'GET'): RequestDto
    {
        $this->continueOnAuthorized($systemInstall);

        $dto = new RequestDto($method, new Uri(self::BASE_URL));
        $dto->setHeaders($this->getHeaders());

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface
     * @throws SystemException
     */
    public function getSubscribeRequester(SystemInstall $systemInstall): RequesterInterface
    {
        $this->continueOnAuthorized($systemInstall);

        return new PipedriveSubscribeRequester($systemInstall, $this->getHeaders());
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface
     */
    public function getUnsubscribeRequester(SystemInstall $systemInstall): RequesterInterface
    {
        $this->continueOnAuthorized($systemInstall);

        return new PipedriveUnsubscribeRequester($systemInstall, $this->getHeaders());
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
            self::API_TOKEN,
            'Api token',
            $this->prepareValue(self::API_TOKEN, $sett),
            TRUE
        );

        $field2 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_CREATE,
            'Hard bounce',
            $systemInstall->isEventHardBounce(),
            TRUE
        );

        $field3 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_UNSUBSCRIBE,
            'Hard bounce',
            $systemInstall->isEventHardBounce(),
            TRUE
        );

        $field4 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_HARD_BOUNCE,
            'Hard bounce',
            $systemInstall->isEventHardBounce(),
            TRUE
        );

        $form = new Form();
        $form->addField($field1)
            ->addField($field2)
            ->addField($field3)
            ->addField($field4);

        return $form->toArray();
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface
     */
    public function getCMEventRequester(SystemInstall $systemInstall): RequesterInterface
    {
        return new PipedriveCMEventRequester($systemInstall, $this->getHeaders(), $this->dm);
    }

    /**
     * ----------------------------------------------- HELPERS -----------------------------------------------
     */

    /**
     * @return array
     */
    private function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }

}