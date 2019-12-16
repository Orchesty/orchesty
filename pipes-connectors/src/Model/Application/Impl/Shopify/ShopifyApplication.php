<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify;

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
 * Class ShopifyApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify
 */
final class ShopifyApplication extends BasicApplicationAbstract implements WebhookApplicationInterface
{
    public const  SHOP            = 'shop';
    public const  SHOPIFY_URL     = 'myshopify.com/admin/api';
    public const  SHOPIFY_VERSION = '2019-10';

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
        return 'shopify';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Shopify';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Shopify v1';
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
        $uri     = sprintf('%s/%s', $this->getBaseUrl($applicationInstall), $url);
        $request = new RequestDto($method, $this->getUri($uri));
        $request->setHeaders(
            [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
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
        $field       = new Field(Field::TEXT, BasicApplicationAbstract::USER, 'Api Key', NULL, TRUE);
        $fieldSecret = new Field(Field::TEXT, BasicApplicationAbstract::PASSWORD, 'Password', NULL, TRUE);
        $form->addField($field);
        $form->addField($fieldSecret);

        return $form;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    private function getPassword(ApplicationInstall $applicationInstall): string
    {
        return $applicationInstall->getSettings(
        )[BasicApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::PASSWORD];
    }

    /**
     * @return WebhookSubscription[]
     */
    public function getWebhookSubscriptions(): array
    {
        return [
            new WebhookSubscription('New customer', 'Webhook', '', ['name' => 'customers/create']),
        ];
    }

    /***
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
            'webhooks.json',
            Json::encode(
                [
                    'webhook' =>
                        [
                            'address' => $url,
                            'topic'   => $subscription->getParameters()['name'],
                            'format' => 'json',
                        ],
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
            sprintf('webhooks/%s.json', $id)
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

        return (string) Json::decode($dto->getBody())['webhook']['id'];
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
    private function getApiKey(ApplicationInstall $applicationInstall): string
    {
        return $applicationInstall->getSettings(
        )[BasicApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::USER];
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    private function getShopName(ApplicationInstall $applicationInstall): string
    {
        return $applicationInstall->getSettings()[BasicApplicationInterface::AUTHORIZATION_SETTINGS][self::SHOP];
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    private function getBaseUrl(ApplicationInstall $applicationInstall): string
    {
        return sprintf(
            'https://%s:%s@%s.%s/%s',
            $this->getApiKey($applicationInstall),
            $this->getPassword($applicationInstall),
            $this->getShopName($applicationInstall),
            self::SHOPIFY_URL,
            self::SHOPIFY_VERSION
        );
    }

}
