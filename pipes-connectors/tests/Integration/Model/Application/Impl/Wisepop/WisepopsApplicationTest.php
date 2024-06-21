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
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class WisepopsApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Wisepop
 */
#[CoversClass(WisepopsApplication::class)]
final class WisepopsApplicationTest extends KernelTestCaseAbstract
{

    /**
     * @var WisepopsApplication
     */
    private WisepopsApplication $application;

    /**
     * @return void
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(ApplicationTypeEnum::WEBHOOK->value, $this->application->getApplicationType());
    }

    /**
     * @return void
     */
    public function testGetKey(): void
    {
        self::assertEquals('wisepops', $this->application->getName());
    }

    /**
     * @return void
     */
    public function testGetName(): void
    {
        self::assertEquals('Wisepops', $this->application->getPublicName());
    }

    /**
     * @return void
     */
    public function testGetDescription(): void
    {
        self::assertEquals('Build website popups.', $this->application->getDescription());
    }

    /**
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
                'Accept'        => 'application/json',
                'Authorization' => 'WISEPOPS-API key="123"',
                'Content-Type'  => 'application/json',
            ],
            $dto->getHeaders(),
        );
    }

    /**
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
     * @return void
     */
    public function testGetWebhookSubscriptions(): void
    {
        self::assertNotEmpty($this->application->getWebhookSubscriptions());
    }

    /**
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
            Json::encode(['event' => 'email', 'target_url' => 'www.target_url...']),
            $dto->getBody(),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetWebhookUnsubscribeRequestDto(): void
    {
        $applicationInstall = $this->createApplicationInstall();
        $dto                = $this->application->getWebhookUnsubscribeRequestDto(
            $applicationInstall,
            (new Webhook())->setWebhookId('1'),
        );

        self::assertEquals('https://app.wisepops.com/api1/hooks?hook_id=1', $dto->getUriString());
    }

    /**
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
     * @return void
     */
    public function testProcessWebhookUnsubscribeResponse(): void
    {
        $dto = new ResponseDto(200, 'Deleted', '{"id": "123-456-789"}', []);

        self::assertTrue($this->application->processWebhookUnsubscribeResponse($dto));
    }

    /**
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
        return (new ApplicationInstall())
            ->setUser('user')
            ->setKey('wisepops')
            ->setSettings([ApplicationInterface::AUTHORIZATION_FORM => ['api_key' => '123']]);
    }

}
