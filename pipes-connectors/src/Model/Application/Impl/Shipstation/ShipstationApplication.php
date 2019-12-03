<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shipstation;

use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookApplicationInterface;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;

/**
 * Class ShipstationApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shipstation
 */
final class ShipstationApplication extends BasicApplicationAbstract implements WebhookApplicationInterface
{

    public const SHIPSSTATION_URL = 'https://ssapi.shipstation.com';
    public const ORDER_NOTIFY     = 'ORDER_NOTIFY';

    /**
     * @return string
     */
    public function getApplicationType(): string
    {
        return ApplicationTypeEnum::WEBHOOK;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'shipstation';
    }

    /**
     * @return string
     */
    public function getName(): string
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
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getRequestDto(
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL
    ): RequestDto
    {
        $request = new RequestDto($method, $this->getUri($url));
        $request->setHeaders(
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => sprintf('Basic %s', $this->getToken($applicationInstall)),
            ]
        );
        if (isset($data)) {
            $request->setBody($data);
        }

        return $request;
    }

    /**
     * @return Form
     * @throws ApplicationInstallException
     */
    public function getSettingsForm(): Form
    {
        $form        = new Form();
        $field       = new Field(Field::TEXT, BasicApplicationAbstract::USER, 'API Key', NULL, TRUE);
        $fieldSecret = new Field(Field::TEXT, BasicApplicationAbstract::PASSWORD, 'API Secret', NULL, TRUE);
        $form->addField($field);
        $form->addField($fieldSecret);

        return $form;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    public function getToken(ApplicationInstall $applicationInstall): string
    {
        return base64_encode(
            sprintf(
                '%s:%s',
                $applicationInstall->getSettings(
                )[BasicApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationAbstract::USER],
                $applicationInstall->getSettings(
                )[BasicApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationAbstract::PASSWORD]
            )
        );
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
        string $url
    ): RequestDto
    {
        return $this->getRequestDto(
            $applicationInstall,
            CurlManager::METHOD_POST,
            sprintf(
                '%s/webhooks/subscribe',
                ShipstationApplication::SHIPSSTATION_URL
            ),
            Json::encode(
                [
                    'target_url' => $url,
                    'event'      => self::ORDER_NOTIFY,
                    'store_id'   => NULL,
                    'name'       => $subscription->getParameters()['name'],
                ]
            )
        );
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $id
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getWebhookUnsubscribeRequestDto(ApplicationInstall $applicationInstall, string $id): RequestDto
    {
        return $this->getRequestDto(
            $applicationInstall,
            CurlManager::METHOD_DELETE,
            sprintf(
                '%s/webhooks/%s',
                ShipstationApplication::SHIPSSTATION_URL,
                $id
            )
        );
    }

    /**
     * @param ResponseDto        $dto
     * @param ApplicationInstall $install
     *
     * @return string
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

}
