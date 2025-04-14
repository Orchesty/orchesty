<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Shopify;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shopify\ShopifyApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\Utils\File\File;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class ShopifyApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Shopify
 */
#[CoversClass(ShopifyApplication::class)]
final class ShopifyApplicationTest extends KernelTestCaseAbstract
{

    private const string ESHOP_NAME = 'hana1';
    private const string PASSWORD   = '079a9710da9264428749be8a148*****';

    /**
     * @var ShopifyApplication
     */
    private ShopifyApplication $application;

    /**
     * @return void
     */
    public function testGetApplicationType(): void
    {
        self::assertSame(ApplicationTypeEnum::WEBHOOK->value, $this->application->getApplicationType());
    }

    /**
     * @return void
     */
    public function testGetKey(): void
    {
        self::assertSame('shopify', $this->application->getName());
    }

    /**
     * @return void
     */
    public function testPublicName(): void
    {
        self::assertSame('Shopify', $this->application->getPublicName());
    }

    /**
     * @return void
     */
    public function testGetDescription(): void
    {
        self::assertSame('Shopify v1', $this->application->getDescription());
    }

    /**
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $applicationInstall = $this->createApplication();

        $request = $this->application->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_POST,
            '/customers.json',
            File::getContent(__DIR__ . '/data/createCustomer.json'),
        );

        self::assertEquals('https://hana1.myshopify.com/admin/api/2020-01/customers.json', $request->getUri());
        self::assertEquals(
            [
                'Accept'                 => 'application/json',
                'Content-Type'           => 'application/json',
                'X-Shopify-Access-Token' => '079a9710da9264428749be8a148*****',
            ],
            $request->getHeaders(),
        );
        self::assertSame(File::getContent(__DIR__ . '/data/createCustomer.json'), $request->getBody());
    }

    /**
     * @throws Exception
     */
    public function testGetFormStack(): void
    {
        $forms = $this->application->getFormStack()->getForms();
        foreach ($forms as $form) {
            foreach ($form->getFields() as $field) {
                self::assertContains(
                    $field->getKey(),
                    [
                        BasicApplicationInterface::USER,
                        BasicApplicationInterface::PASSWORD,
                    ],
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testWebhookSubscribeRequestDto(): void
    {
        $applicationInstall = $this->createApplication();

        $dto = $this->application->getWebhookSubscribeRequestDto(
            $applicationInstall,
            $this->application->getWebhookSubscriptions()[0],
            'https://www.seznam.cz',
        );

        self::assertEquals('https://hana1.myshopify.com/admin/api/2020-01/webhooks.json', $dto->getUri());
    }

    /**
     * @throws CurlException
     * @throws Exception
     */
    public function testGetWebhookUnsubscribeRequestDto(): void
    {
        $applicationInstall = $this->createApplication();
        $dto                = $this->application->getWebhookUnsubscribeRequestDto(
            $applicationInstall,
            (new Webhook())->setWebhookId('759136321675'),
        );

        self::assertEquals('https://hana1.myshopify.com/admin/api/2020-01/webhooks/759136321675.json', $dto->getUri());
    }

    /**
     * @throws Exception
     */
    public function testProcessWebhookSubscribeResponse(): void
    {
        $response = $this->application->processWebhookSubscribeResponse(
            new ResponseDto(200, '', File::getContent(__DIR__ . '/data/createWebhookResponse.json'), []),
            new ApplicationInstall(),
        );
        self::assertSame('1047897672', $response);
    }

    /**
     * @return void
     */
    public function testProcessWebhookUnsubscribeResponse(): void
    {
        $response = $this->application->processWebhookUnsubscribeResponse(
            new ResponseDto(200, '', '', []),
        );
        self::assertEquals(200, $response);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::getContainer()->get('hbpf.application.shopify');
    }

    /**
     * @return ApplicationInstall
     * @throws Exception
     */
    private function createApplication(): ApplicationInstall
    {
        $applicationInstall = DataProvider::getBasicAppInstall($this->application->getName());

        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM =>
                    [
                        BasicApplicationInterface::PASSWORD => self::PASSWORD,
                        BasicApplicationInterface::USER     => self::ESHOP_NAME,
                    ],
            ],
        );

        return $applicationInstall;
    }

}
