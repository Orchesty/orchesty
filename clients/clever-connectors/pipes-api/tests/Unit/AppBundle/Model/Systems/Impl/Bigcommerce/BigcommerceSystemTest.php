<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\BigcommerceSystem;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Nette\Utils\Json;
use Tests\KernelTestCaseAbstract;

/**
 * Class BigcommerceSystemTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce
 */
final class BigcommerceSystemTest extends KernelTestCaseAbstract
{

    private const CLIENT_ID    = 'p7f4o1hfl1zdkz1bp1sy7u8qs0fq7q';
    private const ACCESS_TOKEN = '7ndpkdbqb0h1wycrxhtw43ye0yprtn9';

    /**
     * @var BigcommerceSystem
     */
    private $system;

    /**
     * @var SystemInstall
     */
    private $systemInstall;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->system) {
            $this->system        = new BigcommerceSystem();
            $this->systemInstall = (new SystemInstall())->setSettings([
                'store_id'     => 'ukcfcghi',
                'client_id'    => self::CLIENT_ID,
                'access_token' => self::ACCESS_TOKEN,
            ]);
        }
    }

    /**
     *
     */
    public function testGetSubscribeRequest(): void
    {
        $dto = $this->system->getSubscribeRequest(new WebhookSubscribes(
            'bigcommerce-create-customer-connector',
            'bigcommerce-create-customer',
            'subscribeUrl',
            'unSubscribeUrl'
        ), $this->systemInstall, 'subscribeUrl');

        $this->assertInstanceOf(RequestDto::class, $dto);
        $this->assertEquals('POST', $dto->getMethod());
        $this->assertEquals(self::CLIENT_ID, $dto->getHeaders()['X-Auth-Client']);
        $this->assertEquals(self::ACCESS_TOKEN, $dto->getHeaders()['X-Auth-Token']);
        $this->assertEquals([
            'scope'       => 'store/customer/created',
            'destination' => 'subscribeUrl',
        ], Json::decode($dto->getBody(), TRUE));

        $dto = $this->system->getSubscribeRequest(new WebhookSubscribes(
            'bigcommerce-update-customer-connector',
            'bigcommerce-update-customer',
            'subscribeUrl',
            'unSubscribeUrl'
        ), $this->systemInstall, 'subscribeUrl');

        $this->assertInstanceOf(RequestDto::class, $dto);
        $this->assertEquals('POST', $dto->getMethod());
        $this->assertEquals(self::CLIENT_ID, $dto->getHeaders()['X-Auth-Client']);
        $this->assertEquals(self::ACCESS_TOKEN, $dto->getHeaders()['X-Auth-Token']);
        $this->assertEquals([
            'scope'       => 'store/customer/updated',
            'destination' => 'subscribeUrl',
        ], Json::decode($dto->getBody(), TRUE));

        $dto = $this->system->getSubscribeRequest(new WebhookSubscribes(
            'bigcommerce-delete-customer-connector',
            'bigcommerce-delete-customer',
            'subscribeUrl',
            'unSubscribeUrl'
        ), $this->systemInstall, 'subscribeUrl');

        $this->assertInstanceOf(RequestDto::class, $dto);
        $this->assertEquals('POST', $dto->getMethod());
        $this->assertEquals(self::CLIENT_ID, $dto->getHeaders()['X-Auth-Client']);
        $this->assertEquals(self::ACCESS_TOKEN, $dto->getHeaders()['X-Auth-Token']);
        $this->assertEquals([
            'scope'       => 'store/customer/deleted',
            'destination' => 'subscribeUrl',
        ], Json::decode($dto->getBody(), TRUE));
    }

    /**
     *
     */
    public function testGetUnSubscribeRequest(): void
    {
        $dto = $this->system->getUnsubscribeRequest($this->systemInstall, 'id');

        $this->assertInstanceOf(RequestDto::class, $dto);
        $this->assertEquals('DELETE', $dto->getMethod());
        $this->assertEquals(self::CLIENT_ID, $dto->getHeaders()['X-Auth-Client']);
        $this->assertEquals(self::ACCESS_TOKEN, $dto->getHeaders()['X-Auth-Token']);
    }

    /**
     *
     */
    public function testGetWebHookId(): void
    {
        $this->assertEquals(123456789, $this->system->getWebhookId(new ResponseDto(
            200,
            'OK',
            file_get_contents(sprintf('%s/data/BigcommerceWebhookSubscriptionResponse.json', __DIR__)),
            []
        )));

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $this->system->getWebhookId(new ResponseDto(200, 'OK', '{}', []));
    }

    /**
     *
     */
    public function testIsAuthorized(): void
    {
        $this->assertTrue($this->system->isAuthorized($this->systemInstall));
        $this->systemInstall->setSettings([]);
        $this->assertFalse($this->system->isAuthorized($this->systemInstall));
    }

    /**
     *
     */
    public function testGetSettingsFields(): void
    {
        $form = $this->system->getSettingFields($this->systemInstall);

        $this->assertEquals(3, count($form));
        $this->assertEquals([
            0 => [
                'type'      => 'text',
                'key'       => 'store_id',
                'label'     => 'Store ID (XXX part in https://store-XXX.mybigcommerce.com)',
                'value'     => 'ukcfcghi',
                'required'  => TRUE,
                'read_only' => FALSE,
            ],
            1 => [
                'type'      => 'text',
                'key'       => 'client_id',
                'label'     => 'Client ID',
                'value'     => 'p7f4o1hfl1zdkz1bp1sy7u8qs0fq7q',
                'required'  => TRUE,
                'read_only' => FALSE,
            ],
            2 => [
                'type'      => 'text',
                'key'       => 'access_token',
                'label'     => 'Access Token',
                'value'     => '7ndpkdbqb0h1wycrxhtw43ye0yprtn9',
                'required'  => TRUE,
                'read_only' => FALSE,
            ],
        ], $form);
    }

}