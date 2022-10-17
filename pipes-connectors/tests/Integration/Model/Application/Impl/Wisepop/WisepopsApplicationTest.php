<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Wisepop;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;

/**
 * Class WisepopsApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Wisepop
 */
final class WisepopsApplicationTest extends DatabaseTestCaseAbstract
{

    /**
     * @var WisepopsApplication
     */
    private WisepopsApplication $application;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getApplicationType
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(ApplicationTypeEnum::WEBHOOK, $this->application->getApplicationType());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getName
     */
    public function testGetKey(): void
    {
        self::assertEquals('wisepops', $this->application->getName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getPublicName
     */
    public function testGetName(): void
    {
        self::assertEquals('Wisepops', $this->application->getPublicName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getDescription
     */
    public function testGetDescription(): void
    {
        self::assertEquals('Build website popups.', $this->application->getDescription());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getRequestDto
     *
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $applicationInstall = $this->createApplicationInstall();
        $dto                = $this->application->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_GET,
            'https://app.wisepops.com/api1/wisepops',
        );

        self::assertEquals(
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'WISEPOPS-API key="123"',
            ],
            $dto->getHeaders(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getFormStack
     *
     * @throws Exception
     */
    public function testGetFormStack(): void
    {
        $forms = $this->application->getFormStack()->getForms();
        foreach ($forms as $form) {
            foreach ($form->getFields() as $field) {
                self::assertContains($field->getKey(), ['api_key']);
            }
        }
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getWebhookSubscriptions
     */
    public function testGetWebhookSubscriptions(): void
    {
        self::assertNotEmpty($this->application->getWebhookSubscriptions());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getWebhookSubscribeRequestDto
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getRequestDto
     *
     * @throws Exception
     */
    public function testGetWebhookSubscribeRequestDto(): void
    {
        $applicationInstall = $this->createApplicationInstall();

        $dto = $this->application->getWebhookSubscribeRequestDto(
            $applicationInstall,
            new WebhookSubscription('test', 'test', 'test', ['name' => 'email']),
            'www.target_url...',
        );

        self::assertEquals(
            Json::encode(['target_url' => 'www.target_url...', 'event' => 'email']),
            $dto->getBody(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getWebhookUnsubscribeRequestDto
     *
     * @throws Exception
     */
    public function testGetWebhookUnsubscribeRequestDto(): void
    {
        $applicationInstall = $this->createApplicationInstall();
        $dto                = $this->application->getWebhookUnsubscribeRequestDto(
            $applicationInstall,
            new Webhook('1'),
        );

        self::assertEquals('https://app.wisepops.com/api1/hooks?hook_id=1', $dto->getUriString());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::processWebhookSubscribeResponse
     *
     * @throws Exception
     */
    public function testProcessWebhookSubscribeResponse(): void
    {
        $applicationInstall = $this->createApplicationInstall();
        $dto                = new ResponseDto(200, 'Created', '{"id": "123-456-789"}', []);

        self::assertEquals(
            '123-456-789',
            $this->application->processWebhookSubscribeResponse($dto, $applicationInstall),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::processWebhookUnsubscribeResponse
     */
    public function testProcessWebhookUnsubscribeResponse(): void
    {
        $dto = new ResponseDto(200, 'Deleted', '{"id": "123-456-789"}', []);

        self::assertTrue($this->application->processWebhookUnsubscribeResponse($dto));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::isAuthorized
     *
     * @throws Exception
     */
    public function testIsAuthorized(): void
    {
        self::assertTrue($this->application->isAuthorized($this->createApplicationInstall()));
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::getContainer()->get('hbpf.application.wisepops');
    }

    /**
     * @throws Exception
     */
    private function createApplicationInstall(): ApplicationInstall
    {
        $applicationInstall = (new ApplicationInstall())
            ->setUser('user')
            ->setKey('wisepops')
            ->setSettings([ApplicationInterface::AUTHORIZATION_FORM => ['api_key' => '123']]);
        $this->pfd($applicationInstall);

        return $applicationInstall;
    }

}
