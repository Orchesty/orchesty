<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\AmazonApps\S3;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class S3ApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\S3
 */
final class S3ApplicationTest extends DatabaseTestCaseAbstract
{

    /**
     * @var S3Application
     */
    private $application;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::$container->get('hbpf.application.s3');
    }

    /**
     * @covers S3Application::isAuthorized
     */
    public function testIsAuthorized(): void
    {
        $application = (new ApplicationInstall())->setSettings([
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

        self::assertTrue($this->application->isAuthorized($application));
    }

    /**
     * @covers S3Application::isAuthorized
     */
    public function testIsNotAuthorized(): void
    {
        $application = new ApplicationInstall();

        $this->dm->persist($application);
        $this->dm->flush();

        self::assertFalse($this->application->isAuthorized($application));
    }

}
