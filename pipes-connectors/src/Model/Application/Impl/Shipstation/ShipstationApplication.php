<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shipstation;

use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\Utils\String\Json;
use JsonException;

/**
 * Class ShipstationApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shipstation
 */
final class ShipstationApplication extends BasicApplicationAbstract implements WebhookApplicationInterface
{

    public const SHIPSTATION_URL = 'https://ssapi.shipstation.com';
    public const ORDER_NOTIFY    = 'ORDER_NOTIFY';

    /**
     * @return string
     */
    public function getApplicationType(): string
    {
        return ApplicationTypeEnum::WEBHOOK->value;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'shipstation';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
    {
        return 'Shipstation';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Shipstation v1';
    }

    /**
     * @param ProcessDtoAbstract $dto
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getRequestDto(
        ProcessDtoAbstract $dto,
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
    ): RequestDto
    {
        $request = new RequestDto($this->getUri($url), $method, $dto);
        $request->setHeaders(
            [
                'Accept'        => 'application/json',
                'Authorization' => sprintf('Basic %s', $this->getToken($applicationInstall)),
                'Content-Type'  => 'application/json',
            ],
        );
        if (isset($data)) {
            $request->setBody($data);
        }

        return $request;
    }

    /**
     * @return FormStack
     */
    public function getFormStack(): FormStack
    {
        $form        = new Form(ApplicationInterface::AUTHORIZATION_FORM, 'Authorization settings');
        $field       = new Field(Field::TEXT, BasicApplicationAbstract::USER, 'API Key', NULL, TRUE);
        $fieldSecret = new Field(Field::TEXT, BasicApplicationAbstract::PASSWORD, 'API Secret', NULL, TRUE);
        $form->addField($field);
        $form->addField($fieldSecret);

        $formStack = new FormStack();
        $formStack->addForm($form);

        return $formStack;
    }

    /**
     * @return WebhookSubscription[]
     */
    public function getWebhookSubscriptions(): array
    {
        return [
            new WebhookSubscription('New order', 'Webhook', '', ['name' => self::ORDER_NOTIFY]),
        ];
    }

    /**
     * @param ApplicationInstall  $applicationInstall
     * @param WebhookSubscription $subscription
     * @param string              $url
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getWebhookSubscribeRequestDto(
        ApplicationInstall $applicationInstall,
        WebhookSubscription $subscription,
        string $url,
    ): RequestDto
    {
        return $this->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_POST,
            sprintf(
                '%s/webhooks/subscribe',
                self::SHIPSTATION_URL,
            ),
            Json::encode(
                [
                    'event'      => self::ORDER_NOTIFY,
                    'name'       => $subscription->getParameters()['name'],
                    'store_id'   => NULL,
                    'target_url' => $url,
                ],
            ),
        );
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param Webhook            $webhook
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getWebhookUnsubscribeRequestDto(
        ApplicationInstall $applicationInstall,
        Webhook $webhook,
    ): RequestDto
    {
        return $this->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_DELETE,
            sprintf(
                '%s/webhooks/%s',
                self::SHIPSTATION_URL,
                $webhook->getWebhookId(),
            ),
        );
    }

    /**
     * @param ResponseDto        $dto
     * @param ApplicationInstall $install
     *
     * @return string
     * @throws JsonException
     */
    public function processWebhookSubscribeResponse(ResponseDto $dto, ApplicationInstall $install): string
    {
        $install;

        return Json::decode($dto->getBody())['id'];
    }

    /**
     * @param ResponseDto $dto
     *
     * @return bool
     */
    public function processWebhookUnsubscribeResponse(ResponseDto $dto): bool
    {
        return $dto->getStatusCode() === 200;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    private function getToken(ApplicationInstall $applicationInstall): string
    {
        return base64_encode(
            sprintf(
                '%s:%s',
                $applicationInstall->getSettings(
                )[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationAbstract::USER],
                $applicationInstall->getSettings(
                )[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationAbstract::PASSWORD],
            ),
        );
    }

}
