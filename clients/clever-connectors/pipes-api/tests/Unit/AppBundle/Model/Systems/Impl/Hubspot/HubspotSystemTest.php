<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\HubspotSystem;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Tests\KernelTestCaseAbstract;

/**
 * Class HubspotSystemTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot
 */
final class HubspotSystemTest extends KernelTestCaseAbstract
{

    private const ACCESS_TOKEN = 'sdf5sd46';

    /**
     * @var HubspotSystem|null
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

            $this->system = new HubspotSystem($provider, 'abc');

            $this->systemInstall = new SystemInstall();
            $this->systemInstall
                ->setUser('user123')
                ->setToken('token123');
            $this->systemInstall->setSettings([
                'access_token' => self::ACCESS_TOKEN,
                'app_id'       => 12345,
            ]);
        }
    }

    /**
     * @covers HubspotSystem::getSubscribeRequest()
     */
    public function testGetSubscribeRequest(): void
    {
        $params = [
            'subscriptionType' => 'contact.propertyChange',
            'propertyName'     => 'firstname',
        ];

        $webhook = new WebhookSubscribes('buhspot-create-customer-connector', 'top', $params);

        $dto = $this->system->getSubscribeRequester($this->systemInstall)
            ->getRequestDto([RequesterInterface::OBJECT => $webhook]);

        self::assertInstanceOf(RequestDto::class, $dto);
        self::assertArrayNotHasKey('Authorization', $dto->getHeaders());
        self::assertEquals('POST', $dto->getMethod());

        $exp = [
            'subscriptionDetails' => $params,
            'enabled'             => TRUE,
        ];

        self::assertEquals($exp, json_decode($dto->getBody(), TRUE));
    }

    /**
     * @covers HubspotSystem::getUnsubscribeRequest()
     */
    public function testGetUnsubscribeRequest(): void
    {
        $dto = $this->system->getUnsubscribeRequester($this->systemInstall)
            ->getRequestDto([RequesterInterface::WEBHOOK_ID => '1']);

        self::assertInstanceOf(RequestDto::class, $dto);
        self::assertArrayNotHasKey('Authorization', $dto->getHeaders());
        self::assertEquals('DELETE', $dto->getMethod());
    }

    /**
     * @covers HubspotSystem::getWebhookId()
     */
    public function testGetWebhookId(): void
    {
        $sytemInstall = new SystemInstall();
        $sytemInstall->setSettings([
            'access_token' => self::ACCESS_TOKEN,
            'app_id'       => 12345,
        ]);
        $res = file_get_contents(__DIR__ . '/data/HubspotWebhookSubscriptionResponse.json');
        $dto = new ResponseDto(200, '', $res, []);
        $id  = $this->system->getSubscribeRequester($sytemInstall)->processResponse($dto, $sytemInstall);
        self::assertEquals(25, $id);

        $dto = new ResponseDto(200, '', '', []);
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $this->system->getSubscribeRequester($sytemInstall)->processResponse($dto, $sytemInstall);
    }

    /**
     * @covers HubspotSystem::isAuthorized()
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
     * @covers HubspotSystem::getSettingFields()
     */
    public function testGetSettingFields(): void
    {
        /** @var Form $form */
        $form = $this->system->getSettingFields($this->systemInstall);

        self::assertEquals(2, count($form));
        self::assertEquals('app_id', $form[0]['key']);
        self::assertEquals(Field::TEXT, $form[0]['type']);
        self::assertEquals('webhook_url', $form[1]['key']);
        self::assertEquals(Field::URL, $form[1]['type']);
    }

}