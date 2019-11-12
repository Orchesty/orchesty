<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\AmazonApps\Redshift;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class RedshiftApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Redshift
 */
final class RedshiftApplicationTest extends DatabaseTestCaseAbstract
{

    /**
     * @var RedshiftApplication
     */
    private $application;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::$container->get('hbpf.application.redshift');
    }

    /**
     * @covers S3Application::isAuthorized
     */
    public function testIsAuthorized(): void
    {
        $application = (new ApplicationInstall())->setSettings(
            [
                BasicApplicationAbstract::FORM => [
                    RedshiftApplication::KEY         => 'Key',
                    RedshiftApplication::SECRET      => 'Secret',
                    RedshiftApplication::REGION      => 'eu-central-1',
                    RedshiftApplication::DB_PASSWORD => 'dbPasswd',
                ],
            ]
        );

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
