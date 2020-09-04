<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Pipedrive;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Pipedrive\PipedriveApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class PipedriveApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Pipedrive
 */
final class PipedriveApplicationTest extends DatabaseTestCaseAbstract
{

    public const TOKEN = 'ebcebe5e73aa8ba62**********80c05377fcd63';

    /**
     * @var PipedriveApplication
     */
    private $application;

    /**
     * @throws Exception
     */
    public function testWebhookSubscribeRequestDto(): void
    {
        $applicationInstall = DataProvider::getBasicAppInstall(
            $this->application->getKey(),
            self::TOKEN
        );

        $subscription = new WebhookSubscription(
            'New activity',
            'node',
            'xxx',
            ['action' => 'added', 'object' => 'activity']
        );

        $request = $this->application->getWebhookSubscribeRequestDto(
            $applicationInstall,
            $subscription,
            'https://seznam.cz'
        );

        $requestUn = $this->application->getWebhookUnsubscribeRequestDto($applicationInstall, '388');

        self::assertEquals(
            'https://api.pipedrive.com/v1/webhooks?api_token=ebcebe5e73aa8ba62**********80c05377fcd63',
            $request->getUriString()
        );

        self::assertEquals(
            '{"subscription_url":"https:\/\/seznam.cz","event_action":"added","event_object":"activity"}',
            $request->getBody()
        );

        self::assertEquals(
            'https://api.pipedrive.com/v1/webhooks/388?api_token=ebcebe5e73aa8ba62**********80c05377fcd63',
            $requestUn->getUriString()
        );
    }

    /**
     *
     */
    public function testName(): void
    {
        self::assertEquals('Pipedrive', $this->application->getName());
    }

    /**
     *
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(ApplicationTypeEnum::WEBHOOK, $this->application->getApplicationType());
    }

    /**
     *
     */
    public function testGetDescription(): void
    {
        self::assertEquals('Pipedrive v1', $this->application->getDescription());
    }

    /**
     *
     */
    public function testGetWebhookSubscriptions(): void
    {
        $webhookSubcription = $this->application->getWebhookSubscriptions();
        self::assertEquals(PipedriveApplication::ADDED, $webhookSubcription[0]->getParameters()['action']);
        self::assertEquals(PipedriveApplication::ACTIVITY, $webhookSubcription[0]->getParameters()['object']);
    }

    /**
     * @throws Exception
     */
    public function testGetSettingsForm(): void
    {
        $field = $this->application->getSettingsForm()->getFields();
        self::assertContains($field[0]->getKey(), ['user']);
    }

    /**
     * @throws Exception
     */
    public function testProcessWebhookSubscribeResponse(): void
    {
        $response = $this->application->processWebhookSubscribeResponse(
            new ResponseDto(201, '', '{"data": {"id": 88888}}', []),
            new ApplicationInstall()
        );
        self::assertEquals('88888', $response);
    }

    /**
     *
     */
    public function testProcessWebhookUnsubscribeResponse(): void
    {
        $response = $this->application->processWebhookUnsubscribeResponse(
            new ResponseDto(200, '', '{"id":"id88"}', [])
        );
        self::assertEquals(200, $response);
    }

    /**
     * @throws Exception
     */
    public function testIsAuthorized(): void
    {
        $applicationInstall = DataProvider::getBasicAppInstall(
            $this->application->getKey(),
            self::TOKEN
        );
        $this->pfd($applicationInstall);
        $this->dm->clear();

        self::assertEquals(TRUE, $this->application->isAuthorized($applicationInstall));
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::$container->get('hbpf.application.pipedrive');
    }

}
