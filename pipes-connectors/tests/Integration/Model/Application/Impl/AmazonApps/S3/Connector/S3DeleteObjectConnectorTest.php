<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\AmazonApps\S3\Connector;

use Aws\Command;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\Connector\S3DeleteObjectConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;

/**
 * Class S3DeleteObjectConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\AmazonApps\S3\Connector
 */
final class S3DeleteObjectConnectorTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    private const KEY  = 's3';
    private const USER = 'user';

    /**
     * @var S3DeleteObjectConnector
     */
    private S3DeleteObjectConnector $connector;

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->createApplication();

        $dto = (new ProcessDto())
            ->setData(Json::encode(['name' => 'Test']))
            ->setHeaders(['application' => self::KEY, 'user' => self::USER]);
        $dto = $this->connector->processAction($dto);

        self::assertEquals('Test', Json::decode($dto->getData())['name']);
    }

    /**
     * @throws Exception
     */
    public function testProcessActionMissingName(): void
    {
        self::assertException(
            ConnectorException::class,
            NULL,
            "Connector 's3-delete-object': Required parameter 'name' is not provided!",
        );

        $this->createApplication();

        $dto = (new ProcessDto())->setHeaders(['application' => self::KEY, 'user' => self::USER]);

        $this->connector->processAction($dto);
    }

    /**
     * @throws Exception
     */
    public function testProcessActionException(): void
    {
        self::assertException(
            OnRepeatException::class,
            0,
            "Connector 's3-delete-object': Aws\S3\Exception\S3Exception: Something gone wrong!",
        );

        $this->createApplication();

        $client = self::createPartialMock(S3Client::class, ['__call']);
        $client->method('__call')->willReturnCallback(
            static function (): void {
                throw new S3Exception('Something gone wrong!', new Command('Unknown'));
            },
        );

        $application = self::createPartialMock(S3Application::class, ['getS3Client']);
        $application->method('getS3Client')->willReturn($client);
        $this->setProperty($this->connector, 'application', $application);

        $dto = (new ProcessDto())
            ->setData(Json::encode(['name' => 'Test', 'content' => 'Content']))
            ->setHeaders(['application' => self::KEY, 'user' => self::USER]);

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
            ->setHeaders(['application' => self::KEY, 'user' => self::USER]);

        self::getContainer()
            ->get('hbpf.connector.s3-create-object')
            ->processAction($dto);

        $this->connector = self::getContainer()->get('hbpf.connector.s3-delete-object');
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
                    ApplicationInterface::AUTHORIZATION_FORM => [
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
