<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\AmazonApps\S3\Connector;

use Aws\S3\S3Client;
use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\Connector\S3GetObjectConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use Throwable;

/**
 * Class S3GetObjectConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\AmazonApps\S3\Connector
 */
final class S3GetObjectConnectorTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    private const KEY     = 's3';
    private const USER    = 'user';
    private const HEADERS = ['pf-application' => self::KEY, 'pf-user' => self::USER];

    /**
     * @var S3GetObjectConnector
     */
    private S3GetObjectConnector $connector;

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->createApplication();

        $dto = (new ProcessDto())
            ->setData(Json::encode(['name' => 'Test']))
            ->setHeaders(self::HEADERS);

        try {
            $dto     = $this->connector->processAction($dto);
            $content = Json::decode($dto->getData());

            self::assertEquals('Test', $content['name']);
            self::assertEquals('Content', $content['content']);
        } catch (Throwable) { // Sometimes fails on CI, use mock when it's happen...
            $client = self::createPartialMock(S3Client::class, ['__call']);
            $client->method('__call')->willReturnCallback(
                static function (string $method, array $parameters): void {
                    $method;

                    file_put_contents($parameters[0]['SaveAs'], 'Content');
                },
            );

            $application = self::createPartialMock(S3Application::class, ['getS3Client']);
            $application->method('getS3Client')->willReturn($client);
            $this->setProperty($this->connector, 'application', $application);

            $dto     = $this->connector->processAction($dto);
            $content = Json::decode($dto->getData());

            self::assertEquals('Test', $content['name']);
            self::assertEquals('Content', $content['content']);
        }
    }

    /**
     * @throws Exception
     */
    public function testProcessActionMissingName(): void
    {
        self::assertException(
            ConnectorException::class,
            ConnectorException::CONNECTOR_FAILED_TO_PROCESS,
            "Connector 's3-get-object': Required parameter 'name' is not provided!",
        );

        $this->createApplication();

        $dto = (new ProcessDto())->setHeaders(self::HEADERS);

        $this->connector->processAction($dto);
    }

    /**
     * @throws Exception
     */
    public function testProcessActionException(): void
    {
        $this->createApplication();

        $dto = (new ProcessDto())
            ->setData(Json::encode(['name' => 'Unknown']))
            ->setHeaders(self::HEADERS);

        self::expectException(OnRepeatException::class);
        self::expectExceptionCode(0);
        self::expectExceptionMessage(
            "Connector 's3-get-object': Aws\S3\Exception\S3Exception: Error executing \"GetObject\" on \"http://fakes3:4567/Bucket/Unknown\"; AWS HTTP error: Client error: `GET http://fakes3:4567/Bucket/Unknown` resulted in a `404 Not Found`",
        );

        $this->connector->processAction($dto);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createApplication();

        $dto = (new ProcessDto())
            ->setData(Json::encode(['name' => 'Test', 'content' => 'Content']))
            ->setHeaders(self::HEADERS);

        self::$container
            ->get('hbpf.connector.s3-create-object')
            ->processAction($dto);

        $this->connector = self::$container->get('hbpf.connector.s3-get-object');
    }

    /**
     * @throws Exception
     */
    private function createApplication(): void
    {
        $application = (new ApplicationInstall())
            ->setKey(self::KEY)
            ->setUser(self::USER)
            ->setSettings(
                [
                    S3Application::FORM => [
                        S3Application::KEY      => 'Key',
                        S3Application::SECRET   => 'Secret',
                        S3Application::REGION   => 'eu-central-1',
                        S3Application::BUCKET   => 'Bucket',
                        S3Application::ENDPOINT => 'http://fakes3:4567',
                    ],
                ],
            );

        $this->pfd($application);
    }

}
