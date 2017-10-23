<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops;

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
 * Class WisepopsSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops
 */
class WisepopsSystem implements WebhookSystemInterface, AuthorizationInterface
{

    use AuthorizationTrait;
    use WebhookSystemTrait;

    private const API_KEY = 'api_key';

    private const BASE_URL    = 'https://app.wisepops.com/';
    private const WEBHOOK_URL = 'https://app.wisepops.com/api1/hooks';

    /**
     * WisepopsSystem constructor.
     */
    function __construct()
    {
        $this->subscriptions[] = new WebhookSubscribes('wisepops-create-email-connector', 'wisepops-create-email',
            self::WEBHOOK_URL, self::WEBHOOK_URL);
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
    public function getKey(): string
    {
        return 'wisepops';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'WisePOPS';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'WisePOPS system.';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'WisePOPS logo';
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        return !empty($systemInstall->getSettings()[self::API_KEY] ?? '');
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
        if (!$this->isAuthorized($systemInstall)) {
            throw new SystemException(
                'WisePOPS is not authorized.',
                SystemException::SYSTEM_IS_UNAUTHORIZED
            );
        }

        $dto = new RequestDto($method, new Uri(self::BASE_URL));
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
        $field1 = new Field(
            Field::TEXT,
            self::API_KEY,
            'Api key.',
            $this->prepareValue(self::API_KEY, $systemInstall->getSettings()),
            TRUE
        );

        $form = new Form();
        $form->addField($field1);

        return $form->toArray();
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
            throw new SystemException(
                'WisePOPS is not authorized.',
                SystemException::SYSTEM_IS_UNAUTHORIZED
            );
        }

        $dto = new RequestDto('POST', new Uri($subscription->getRegistrationUrl()));
        $dto->setHeaders($this->getHeaders($systemInstall));
        $dto->setBody(sprintf('{"event":"email", "target_url":"%s"}', $url));

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $webhookId
     *
     * @return RequestDto
     * @throws SystemException
     */
    public function getUnsubscribeRequest(SystemInstall $systemInstall, string $webhookId): RequestDto
    {
        if (!$this->isAuthorized($systemInstall)) {
            throw new SystemException(
                'WisePOPS is not authorized.',
                SystemException::SYSTEM_IS_UNAUTHORIZED
            );
        }

        $dto = new RequestDto('DELETE', new Uri(sprintf(self::WEBHOOK_URL . '?hook_id=%s', $webhookId)));
        $dto->setHeaders($this->getHeaders($systemInstall));

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
        $data = json_decode($response->getBody(), TRUE);
        if (!isset($data['id'])) {
            throw new CleverConnectorsException(
                'Missing webhookId in response from subscription request.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return (string) $data['id'];
    }

    /**
     * ------------------------------------------------ HELPERS ------------------------------------------------
     */

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    private function getHeaders(SystemInstall $systemInstall): array
    {
        return [
            'Content-Type'  => 'application/json',
            'Authorization' => sprintf('WISEPOPS-API key="%s"', $systemInstall->getSettings()[self::API_KEY]),
        ];
    }

}