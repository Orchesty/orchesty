<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
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
        $webhook = new WebhookSubscribes(
            'bigcommerce-create-customer-connector',
            'bigcommerce-create-customer'
        );
        $dto     = $this->system->getSubscribeRequester($this->systemInstall)
            ->getRequestDto([
                RequesterInterface::OBJECT      => $webhook,
                RequesterInterface::WEBHOOK_URL => 'subscribeUrl',
            ]);

        $this->assertInstanceOf(RequestDto::class, $dto);
        $this->assertEquals('POST', $dto->getMethod());
        $this->assertEquals(self::CLIENT_ID, $dto->getHeaders()['X-Auth-Client']);
        $this->assertEquals(self::ACCESS_TOKEN, $dto->getHeaders()['X-Auth-Token']);
        $this->assertEquals([
            'scope'       => 'store/customer/created',
            'destination' => 'subscribeUrl',
        ], Json::decode($dto->getBody(), TRUE));

        $webhook = new WebhookSubscribes(
            'bigcommerce-update-customer-connector',
            'bigcommerce-update-customer'
        );

        $dto = $this->system->getSubscribeRequester($this->systemInstall)
            ->getRequestDto([
                RequesterInterface::OBJECT      => $webhook,
                RequesterInterface::WEBHOOK_URL => 'subscribeUrl',
            ]);

        $this->assertInstanceOf(RequestDto::class, $dto);
        $this->assertEquals('POST', $dto->getMethod());
        $this->assertEquals(self::CLIENT_ID, $dto->getHeaders()['X-Auth-Client']);
        $this->assertEquals(self::ACCESS_TOKEN, $dto->getHeaders()['X-Auth-Token']);
        $this->assertEquals([
            'scope'       => 'store/customer/updated',
            'destination' => 'subscribeUrl',
        ], Json::decode($dto->getBody(), TRUE));

        $webhook = new WebhookSubscribes(
            'bigcommerce-delete-customer-connector',
            'bigcommerce-delete-customer'
        );

        $dto = $this->system->getSubscribeRequester($this->systemInstall)
            ->getRequestDto([
                RequesterInterface::OBJECT      => $webhook,
                RequesterInterface::WEBHOOK_URL => 'subscribeUrl',
            ]);

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
        $dto = $this->system->getUnsubscribeRequester($this->systemInstall)
            ->getRequestDto([RequesterInterface::WEBHOOK_ID => 'id']);

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
        $resp = new ResponseDto(
            200,
            'OK',
            file_get_contents(sprintf('%s/data/BigcommerceWebhookSubscriptionResponse.json', __DIR__)),
            []
        );

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            'store_id'     => 'store_id', 'client_id' => 'client_id',
            'access_token' => 'access_token',
        ]);

        $this->assertEquals(123456789,
            $this->system->getSubscribeRequester($systemInstall)->processResponse($resp, $systemInstall));

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $this->system->getSubscribeRequester($systemInstall)->processResponse(new ResponseDto(200, 'OK', '{}', []),
            $systemInstall);
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

        $this->assertEquals(5, count($form));
        $this->assertEquals([
            0 => [
                'type'        => 'text',
                'key'         => 'store_id',
                'label'       => 'Store ID (XXX part in https://store-XXX.mybigcommerce.com)',
                'value'       => 'ukcfcghi',
                'required'    => TRUE,
                'read_only'   => FALSE,
                'disabled'    => FALSE,
                'description' => '',
                'choices'     => [],
                'action'      => '',
                'depends_on'  => '',
            ],
            1 => [
                'type'        => 'text',
                'key'         => 'client_id',
                'label'       => 'Client ID',
                'value'       => 'p7f4o1hfl1zdkz1bp1sy7u8qs0fq7q',
                'required'    => TRUE,
                'read_only'   => FALSE,
                'disabled'    => FALSE,
                'description' => '',
                'choices'     => [],
                'action'      => '',
                'depends_on'  => '',
            ],
            2 => [
                'type'        => 'text',
                'key'         => 'access_token',
                'label'       => 'Access Token',
                'value'       => '7ndpkdbqb0h1wycrxhtw43ye0yprtn9',
                'required'    => TRUE,
                'read_only'   => FALSE,
                'disabled'    => FALSE,
                'description' => '',
                'choices'     => [],
                'action'      => '',
                'depends_on'  => '',
            ],
            3 => [
                'type'        => 'checkbox',
                'key'         => 'eventCreate',
                'label'       => 'CM create event',
                'value'       => FALSE,
                'required'    => FALSE,
                'read_only'   => FALSE,
                'disabled'    => FALSE,
                'description' => '',
                'choices'     => [],
                'action'      => '',
                'depends_on'  => '',
            ],
            4 => [
                'type'        => 'select',
                'key'         => 'list',
                'label'       => 'Distribution list',
                'value'       => NULL,
                'required'    => FALSE,
                'read_only'   => FALSE,
                'disabled'    => FALSE,
                'description' => '',
                'choices'     => [],
                'action'      => '',
                'depends_on'  => '',
            ],
        ], $form);
    }

}