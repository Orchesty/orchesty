<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;

/**
 * Class PipedriveSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive
 */
class PipedriveSystem implements WebhookSystemInterface, AuthorizationInterface
{

    use AuthorizationTrait;
    use WebhookSystemTrait;

    public const API_TOKEN = 'api_token';

    private const WEBHOOK_SUBSCRIPTION_URL   = 'https://api.pipedrive.com/v1/webhooks?api_token=%s';
    private const WEBHOOK_UNSUBSCRIPTION_URL = 'https://api.pipedrive.com/v1/webhooks/%s?api_token=%s';
    private const BASE_URL                   = 'https://api.pipedrive.com/v1/';

    /**
     * @var array
     */
    private $events = [
        'pipedrive-update-person-connector' => [
            'updated', 'person',
        ],
        'pipedrive-delete-person-connector' => [
            'deleted', 'person',
        ],
    ];

    /**
     * PipedriveSystem constructor.
     */
    function __construct()
    {
        $this->subscriptions[] = new WebhookSubscribes('pipedrive-update-person-connector', 'pipedrive-update-person',
            self::WEBHOOK_SUBSCRIPTION_URL, self::WEBHOOK_UNSUBSCRIPTION_URL);
        $this->subscriptions[] = new WebhookSubscribes('pipedrive-delete-person-connector', 'pipedrive-delete-person',
            self::WEBHOOK_SUBSCRIPTION_URL, self::WEBHOOK_UNSUBSCRIPTION_URL);
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
        if (!$this->isAuthorized($systemInstall)) {
            throw new SystemException('Pipedrive is unauthorized.', SystemException::SYSTEM_IS_UNAUTHORIZED);
        }

        $dto = new RequestDto('GET', new Uri(self::BASE_URL));
        $dto->setHeaders($this->getHeaders());

        return $dto;
    }

    /**
     * @param WebhookSubscribes $subscription
     * @param SystemInstall     $systemInstall
     * @param string            $url
     *
     * @return RequestDto
     * @throws SystemException
     */
    public function getSubscribeRequest(
        WebhookSubscribes $subscription,
        SystemInstall $systemInstall,
        string $url
    ): RequestDto
    {
        if (!$this->isAuthorized($systemInstall)) {
            throw new SystemException('Pipedrive is unauthorized.', SystemException::SYSTEM_IS_UNAUTHORIZED);
        }

        $event = $this->events[$subscription->getNodeName()];
        $sett  = $systemInstall->getSettings();

        $dto = new RequestDto('POST', new Uri(sprintf($subscription->getSubscribeUrl(), $sett[self::API_TOKEN])));
        $dto->setHeaders($this->getHeaders())
            ->setBody(json_encode([
                'subscription_url' => $url,
                'event_action'     => $event[0],
                'event_object'     => $event[1],
            ]));

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $webhookId
     *
     * @return RequestDto
     */
    public function getUnsubscribeRequest(SystemInstall $systemInstall, string $webhookId): RequestDto
    {
        $sett = $systemInstall->getSettings();

        $dto = new RequestDto('DELETE', new Uri(sprintf($this->subscriptions[0]->getUnSubscribeUrl(), $webhookId, $sett[self::API_TOKEN])));
        $dto->setHeaders($this->getHeaders());

        return $dto;
    }

    /**
     * @param ResponseDto $response
     *
     * @return string
     * @throws CleverConnectorsException
     */
    public function getWebhookId(ResponseDto $response): string
    {
        $body = json_decode($response->getBody(), TRUE);

        if (!array_key_exists('data', $body) || !array_key_exists('id', $body['data'])) {
            throw new CleverConnectorsException(
                'Missing webhook data in response.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return (string) $body['data']['id'];
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