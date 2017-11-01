<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Requester\PipedriveSubscribeRequester;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Requester\PipedriveUnsubscribeRequester;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class PipedriveSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive
 */
class PipedriveSystem implements WebhookSystemInterface, AuthorizationInterface
{

    use AuthorizationTrait;
    use WebhookSystemTrait;

    public const  API_TOKEN = 'api_token';
    private const BASE_URL  = 'https://api.pipedrive.com/v1/';

    /**
     * PipedriveSystem constructor.
     */
    function __construct()
    {
        $this->subscriptions[] = new WebhookSubscribes(
            'pipedrive-update-person-connector',
            TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, $this->getKey())
        );
        $this->subscriptions[] = new WebhookSubscribes(
            'pipedrive-delete-person-connector',
            TopologyNameUtils::getTopologyName(TopologyNameUtils::DELETED_SUBSCRIBERS, $this->getKey())
        );
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
        return 'Pipedrive system.';
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
    public function getRequestDto(SystemInstall $systemInstall, string $method): RequestDto
    {
        $this->continueOnAuthorized($systemInstall);

        $dto = new RequestDto('GET', new Uri(self::BASE_URL));
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

        $form = new Form();
        $form->addField($field1);

        return $form->toArray();
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
        ];
    }

}