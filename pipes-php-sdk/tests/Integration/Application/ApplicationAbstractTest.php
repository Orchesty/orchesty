<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class ApplicationAbstractTest
 *
 * @package PipesPhpSdkTests\Integration\Application
 */
final class ApplicationAbstractTest extends KernelTestCaseAbstract
{

    /**
     * @var TestNullApplication
     */
    private TestNullApplication $application;

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract::getLogo
     * @throws Exception
     */
    public function testGetLogo(): void
    {
        self::assertEquals(NULL, $this->application->getLogo());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract::getApplicationType
     * @throws Exception
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(ApplicationTypeEnum::CRON->value, $this->application->getApplicationType());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract::toArray
     * @throws Exception
     */
    public function testToArray(): void
    {
        self::assertEquals(
            [
                'name'               => 'Null',
                'authorization_type' => 'basic',
                'application_type'   => 'cron',
                'key'                => 'null-key',
                'description'        => 'Application for test purposes',
                'info'               => '',
                'logo'               => NULL,
                'isInstallable'      => TRUE,
            ],
            $this->application->toArray(),
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract::getApplicationForms
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall::setKey
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall::setUser
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall::setSettings
     *
     * @throws Exception
     */
    public function testGetApplicationForm(): void
    {
        $applicationInstall = new ApplicationInstall();

        self::assertEquals(3, count($this->application->getApplicationForms(
            $applicationInstall,
        )[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::FIELDS]));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract::saveApplicationForms
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall::setKey
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall::setUser
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall::setSettings
     *
     * @throws Exception
     */
    public function testSetApplicationSettings(): void
    {
        $applicationInstall = new ApplicationInstall();

        $applicationInstall = $this->application->saveApplicationForms(
            $applicationInstall,
            [ApplicationInterface::AUTHORIZATION_FORM => [BasicApplicationInterface::USER => 'myUsername']],
        );

        self::assertEquals(
            'myUsername',
            $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::USER],
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract::getUri
     */
    public function testGetUri(): void
    {
        self::assertEquals(147, $this->application->getUri('google:147')->getPort());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::getContainer()->get('hbpf.application.null');
    }

}
