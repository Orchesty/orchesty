<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\BigcommerceSystem;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use DateTime;
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

    private const SYSTEM_PLAN         = 'system-plan';
    private const SYSTEM_LIMIT_UPDATE = 'system-limit-update';

    private const PLAN_STANDARD   = 'standard';
    private const PLAN_PLUS       = 'plus';
    private const PLAN_PRO        = 'pro';
    private const PLAN_ENTERPRISE = 'enterprise';
    private const PLAN_UNKNOWN    = 'unknown';

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
            ])
                ->setSystem($this->system->getKey())
                ->setUser('user');
        }
    }

    /**
     *
     */
    public function testGetSubscribeRequest(): void
    {
        $webhook = new WebhookSubscribes(
            'bigcommerce-created-customer-connector',
            'bigcommerce-created-customer'
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
            'bigcommerce-updated-customer-connector',
            'bigcommerce-updated-customer'
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
            'bigcommerce-deleted-customer-connector',
            'bigcommerce-deleted-customer'
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
                'label'       => 'Store ID',
                'value'       => 'ukcfcghi',
                'required'    => TRUE,
                'read_only'   => FALSE,
                'disabled'    => FALSE,
                'description' => 'Store ID (XXX part in https://store-XXX.mybigcommerce.com)',
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

    /**
     *
     */
    public function testGetLimitStandard(): void
    {
        $this->setPlan(self::PLAN_STANDARD);

        $this->assertEquals([
            'pf-limit-key'   => 'user-bigcommerce',
            'pf-limit-time'  => 3600,
            'pf-limit-value' => 20000,
        ], $this->getData());
    }

    /**
     *
     */
    public function testGetLimitPlus(): void
    {
        $this->setPlan(self::PLAN_PLUS);;

        $this->assertEquals([
            'pf-limit-key'   => 'user-bigcommerce',
            'pf-limit-time'  => 3600,
            'pf-limit-value' => 20000,
        ], $this->getData());
    }

    /**
     *
     */
    public function testGetLimitPro(): void
    {
        $this->setPlan(self::PLAN_PRO);

        $this->assertEquals([
            'pf-limit-key'   => 'user-bigcommerce',
            'pf-limit-time'  => 3600,
            'pf-limit-value' => 60000,
        ], $this->getData());
    }

    /**
     *
     */
    public function testGetLimitEnterprise(): void
    {
        $this->setPlan(self::PLAN_ENTERPRISE);

        $this->assertEquals(NULL, $this->getData());
    }

    /**
     *
     */
    public function testGetLimitUnknown(): void
    {
        $this->setPlan(self::PLAN_UNKNOWN);

        $this->assertEquals([
            'pf-limit-key'   => 'user-bigcommerce',
            'pf-limit-time'  => 3600,
            'pf-limit-value' => 20000,
        ], $this->getData());
    }

    /**
     *
     */
    public function testSaveLimit(): void
    {
        $systemInstall = $this->system->saveLimit($this->systemInstall, ['plan_level' => self::PLAN_ENTERPRISE]);
        $settings      = $systemInstall->getSettings();
        unset($settings[SystemInstall::SYSTEM_LIMITS][self::SYSTEM_LIMIT_UPDATE]);
        $this->assertEquals([
            'store_id'      => 'ukcfcghi',
            'client_id'     => 'p7f4o1hfl1zdkz1bp1sy7u8qs0fq7q',
            'access_token'  => '7ndpkdbqb0h1wycrxhtw43ye0yprtn9',
            'system_limits' => ['system-plan' => 'enterprise'],
        ], $settings);
    }

    /**
     * @param string $plan
     */
    private function setPlan(string $plan): void
    {
        $this->systemInstall->setSettings([
            SystemInstall::SYSTEM_LIMITS => [
                self::SYSTEM_PLAN         => $plan,
                self::SYSTEM_LIMIT_UPDATE => new DateTime(),
            ],
        ]);
    }

    /**
     * @return array
     */
    private function getData(): ?array
    {
        $dto = $this->system->getLimit($this->systemInstall);

        if ($dto) {
            $data = $dto->toArray();
            unset($data['limit-last-update']);

            return $data;
        }

        return NULL;
    }

}