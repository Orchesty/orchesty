<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\AmazonApps\S3\Connector;

use Aws\Command;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\Connector\S3DeleteObjectConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class S3DeleteObjectConnectorTest
 *
 * @package Tests\Integration\Model\Application\Impl\AmazonApps\S3\Connector
 */
final class S3DeleteObjectConnectorTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    private const KEY  = 's3';
    private const USER = 'user';

    /**
     * @var S3DeleteObjectConnector
     */
    private $connector;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createApplication();

        $dto = (new ProcessDto())
            ->setData(Json::encode(['name' => 'Test', 'content' => 'Content']))
            ->setHeaders(['pf-application' => self::KEY, 'pf-user' => self::USER]);

        self::$container
            ->get('hbpf.connector.s3-create-object')
            ->processAction($dto);

        $this->connector = self::$container->get('hbpf.connector.s3-delete-object');
    }

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->createApplication();

        $dto = (new ProcessDto())
            ->setData(Json::encode(['name' => 'Test']))
            ->setHeaders(['pf-application' => self::KEY, 'pf-user' => self::USER]);
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
            ConnectorException::CONNECTOR_FAILED_TO_PROCESS,
            "Connector 's3-delete-object': Required parameter 'name' is not provided!"
        );

        $this->createApplication();

        $dto = (new ProcessDto())->setHeaders(['pf-application' => self::KEY, 'pf-user' => self::USER]);

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
            "Connector 's3-delete-object': Aws\S3\Exception\S3Exception: Something gone wrong!"
        );

        $this->createApplication();

        /** @var S3Client|MockObject $client */
        $client = self::createPartialMock(S3Client::class, ['__call']);
        $client->method('__call')->willReturnCallback(
            static function (): void {
                throw new S3Exception('Something gone wrong!', new Command('Unknown'));
            }
        );

        /** @var S3Application|MockObject $application */
        $application = self::createPartialMock(S3Application::class, ['getS3Client']);
        $application->method('getS3Client')->willReturn($client);
        $this->setProperty($this->connector, 'application', $application);

        $dto = (new ProcessDto())
            ->setData(Json::encode(['name' => 'Test', 'content' => 'Content']))
            ->setHeaders(['pf-application' => self::KEY, 'pf-user' => self::USER]);

        $this->connector->processAction($dto);
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
                ]
            );

        $this->dm->persist($application);
        $this->dm->flush();
    }

}
