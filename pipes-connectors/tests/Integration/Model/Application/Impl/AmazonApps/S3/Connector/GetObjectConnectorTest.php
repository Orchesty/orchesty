<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\AmazonApps\S3\Connector;

use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\Connector\GetS3ObjectConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class GetObjectConnectorTest
 *
 * @package Tests\Integration\Model\Application\Impl\S3\Connector
 */
final class GetObjectConnectorTest extends DatabaseTestCaseAbstract
{

    private const KEY  = 's3';
    private const USER = 'user';

    /**
     * @var GetS3ObjectConnector
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
            ->setData((string) json_encode(['name' => 'Test', 'content' => 'Content'], JSON_THROW_ON_ERROR))
            ->setHeaders(['pf-application' => self::KEY, 'pf-user' => self::USER]);

        self::$container
            ->get('hbpf.connector.s3-create-object')
            ->processAction($dto);

        $this->connector = self::$container->get('hbpf.connector.s3-get-object');
    }

    /**
     * @covers GetS3ObjectConnector::processAction
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->createApplication();

        $dto     = (new ProcessDto())
            ->setData((string) json_encode(['name' => 'Test'], JSON_THROW_ON_ERROR))
            ->setHeaders(['pf-application' => self::KEY, 'pf-user' => self::USER]);
        $dto     = $this->connector->processAction($dto);
        $content = json_decode($dto->getData(), TRUE, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('Test', $content['name']);
        self::assertEquals('Content', $content['content']);
    }

    /**
     * @covers GetS3ObjectConnector::processAction
     * @throws Exception
     */
    public function testProcessActionMissingName(): void
    {
        $this->createApplication();

        $dto = (new ProcessDto())->setHeaders(['pf-application' => self::KEY, 'pf-user' => self::USER]);

        self::expectException(ConnectorException::class);
        self::expectExceptionCode(ConnectorException::CONNECTOR_FAILED_TO_PROCESS);
        self::expectExceptionMessage("Connector 's3-get-object': Required parameter 'name' is not provided!");

        $this->connector->processAction($dto);
    }

    /**
     * @covers GetS3ObjectConnector::processAction
     * @throws Exception
     */
    public function testProcessActionMissing(): void
    {
        $this->createApplication();

        $dto = (new ProcessDto())
            ->setData((string) json_encode(['name' => 'Unknown'], JSON_THROW_ON_ERROR))
            ->setHeaders(['pf-application' => self::KEY, 'pf-user' => self::USER]);

        self::expectException(OnRepeatException::class);
        self::expectExceptionCode(0);
        self::expectExceptionMessage("Connector 's3-get-object': Aws\S3\Exception\S3Exception: Error executing \"GetObject\" on \"http://fakes3:4567/Bucket/Unknown\"; AWS HTTP error: Client error: `GET http://fakes3:4567/Bucket/Unknown` resulted in a `404 Not Found`");

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
            ->setSettings([
                BasicApplicationAbstract::FORM => [
                    S3Application::KEY      => 'Key',
                    S3Application::SECRET   => 'Secret',
                    S3Application::REGION   => 'eu-central-1',
                    S3Application::BUCKET   => 'Bucket',
                    S3Application::ENDPOINT => 'http://fakes3:4567',
                ],
            ]);

        $this->dm->persist($application);
        $this->dm->flush();
    }

}
