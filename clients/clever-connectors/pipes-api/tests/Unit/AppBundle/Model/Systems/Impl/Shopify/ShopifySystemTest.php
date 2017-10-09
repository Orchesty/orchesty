<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shopify;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\ShopifySystem;
use CleverConnectors\AppBundle\Model\Systems\WebhookSubscribes;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Tests\KernelTestCaseAbstract;

/**
 * Class ShopifySystemTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shopify
 */
class ShopifySystemTest extends KernelTestCaseAbstract
{

    private const ACCESS_TOKEN = 'sdf5sd46';

    /**
     * @var ShopifySystem|null
     */
    private $system = NULL;

    /**
     * @var SystemInstall
     */
    private $systemInstall;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        if (!$this->system) {
            $provider = $this->getMockBuilder(OAuth2Provider::class)->disableOriginalConstructor()->getMock();
            $provider->method('authorize')->willReturn(TRUE);

            $this->system = new ShopifySystem($provider);

            $this->systemInstall = new SystemInstall();
            $this->systemInstall->setSettings([
                'system_url'   => 'systemUrl',
                'access_token' => self::ACCESS_TOKEN,
            ]);
        }
    }

    /**
     *
     */
    public function testGetSubscribeRequest(): void
    {
        $webhook = new WebhookSubscribes('shopify-customer-create', 'top', 'regUrl', 'unregUrl');

        $dto = $this->system->getSubscribeRequest($webhook, $this->systemInstall, 'someUrl');

        self::assertInstanceOf(RequestDto::class, $dto);
        self::assertEquals(self::ACCESS_TOKEN, $dto->getHeaders()['X-Shopify-Access-Token']);
        self::assertEquals('POST', $dto->getMethod());

        $exp = [
            'webhook' => [
                'topic'   => 'customers/create',
                'address' => 'someUrl',
                'format'  => 'json',
            ],
        ];

        self::assertEquals($exp, json_decode($dto->getBody(), TRUE));
    }

    /**
     *
     */
    public function testGetUnsubscribeRequest(): void
    {
        $dto = $this->system->getUnsubscribeRequest($this->systemInstall, '123');

        self::assertInstanceOf(RequestDto::class, $dto);
        self::assertEquals(self::ACCESS_TOKEN, $dto->getHeaders()['X-Shopify-Access-Token']);
        self::assertEquals('DELETE', $dto->getMethod());
    }

    /**
     *
     */
    public function testGetWebhookId(): void
    {
        $res = file_get_contents(__DIR__ . '/data/ShopifyWebhookSubscriptionResponse.json');
        $dto = new ResponseDto(200, '', $res, []);
        $id  = $this->system->getWebhookId($dto);
        self::assertEquals(29752623134, $id);

        $dto = new ResponseDto(200, '', '', []);
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $this->system->getWebhookId($dto);
    }

    /**
     *
     */
    public function testIsAuthorized(): void
    {
        $sett = $this->systemInstall->getSettings();
        $this->systemInstall->setSettings([]);
        self::assertFalse($this->system->isAuthorized($this->systemInstall));
        $this->systemInstall->setSettings($sett);
        self::assertTrue($this->system->isAuthorized($this->systemInstall));
    }

    /**
     *
     */
    public function testGetSettingFields(): void
    {
        /** @var Form $form */
        $form = $this->system->getSettingFields($this->systemInstall);
        self::assertEquals(1, count($form));
        self::assertEquals('ndflakee', $form[0]['key']);
        self::assertEquals(Field::URL, $form[0]['type']);
    }

}