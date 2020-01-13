<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Wisepop;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\Utils\String\Json;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class WisepopsApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Wisepop
 */
final class WisepopsApplicationTest extends DatabaseTestCaseAbstract
{

    /**
     * @var WisepopsApplication
     */
    private $application;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->application = self::$container->get('hbpf.application.wisepops');
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getApplicationType
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(ApplicationTypeEnum::WEBHOOK, $this->application->getApplicationType());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getKey
     */
    public function testGetKey(): void
    {
        self::assertEquals('wisepops', $this->application->getKey());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getName
     */
    public function testGetName(): void
    {
        self::assertEquals('Wisepops', $this->application->getName());
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
     * @throws CurlException
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $applicationInstall = $this->createApplicationInstall();
        $dto                = $this->application->getRequestDto(
            $applicationInstall,
            CurlManager::METHOD_GET,
            'https://app.wisepops.com/api1/wisepops'
        );

        self::assertEquals(
            $dto->getHeaders(),
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'WISEPOPS-API key="123"',
            ]
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getSettingsForm
     *
     * @throws ApplicationInstallException
     */
    public function testGetSettingsForm(): void
    {
        $fields = $this->application->getSettingsForm()->getFields();
        foreach ($fields as $field) {
            self::assertInstanceOf(Field::class, $field);
            self::assertContains($field->getKey(), ['api_key']);
        }
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getWebhookSubscriptions
     */
    public function testGetWebhookSubscriptions(): void
    {
        self::assertIsArray($this->application->getWebhookSubscriptions());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getWebhookSubscribeRequestDto
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getRequestDto
     *
     * @throws CurlException
     * @throws DateTimeException
     */
    public function testGetWebhookSubscribeRequestDto(): void
    {
        $applicationInstall = $this->createApplicationInstall();

        $dto = $this->application->getWebhookSubscribeRequestDto(
            $applicationInstall,
            new WebhookSubscription('test', 'test', 'test', ['name' => 'email']),
            'www.target_url...'
        );

        self::assertEquals(
            Json::encode(['target_url' => 'www.target_url...', 'event' => 'email']),
            $dto->getBody()
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::getWebhookUnsubscribeRequestDto
     *
     * @throws CurlException
     * @throws DateTimeException
     */
    public function testGetWebhookUnsubscribeRequestDto(): void
    {
        $applicationInstall = $this->createApplicationInstall();
        $dto                = $this->application->getWebhookUnsubscribeRequestDto($applicationInstall, '1');

        self::assertEquals('https://app.wisepops.com/api1/hooks?hook_id=1', $dto->getUriString());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop\WisepopsApplication::processWebhookSubscribeResponse
     *
     * @throws DateTimeException
     */
    public function testProcessWebhookSubscribeResponse(): void
    {
        $applicationInstall = $this->createApplicationInstall();
        $dto                = new ResponseDto(200, 'Created', '{"id": "123-456-789"}', []);

        self::assertEquals(
            '123-456-789',
            $this->application->processWebhookSubscribeResponse($dto, $applicationInstall)
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
     * @throws DateTimeException
     */
    public function testIsAuthorized(): void
    {
        self::assertTrue($this->application->isAuthorized($this->createApplicationInstall()));
    }

    /**
     * @return ApplicationInstall
     * @throws DateTimeException
     * @throws Exception
     */
    private function createApplicationInstall(): ApplicationInstall
    {
        $applicationInstall = (new ApplicationInstall())
            ->setUser('user')
            ->setKey('wisepops')
            ->setSettings([ApplicationAbstract::FORM => ['api_key' => '123']]);
        $this->pf($applicationInstall);

        return $applicationInstall;
    }

}
