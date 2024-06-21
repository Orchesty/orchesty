<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class ApplicationAbstractTest
 *
 * @package PipesPhpSdkTests\Integration\Application
 */
#[CoversClass(ApplicationAbstract::class)]
#[CoversClass(ApplicationInstall::class)]
final class ApplicationAbstractTest extends KernelTestCaseAbstract
{

    /**
     * @var TestNullApplication
     */
    private TestNullApplication $application;

    /**
     * @throws Exception
     */
    public function testGetLogo(): void
    {
        self::assertEquals(NULL, $this->application->getLogo());
    }

    /**
     * @throws Exception
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(ApplicationTypeEnum::CRON->value, $this->application->getApplicationType());
    }

    /**
     * @throws Exception
     */
    public function testToArray(): void
    {
        self::assertEquals(
            [
                'application_type'   => 'cron',
                'authorization_type' => 'basic',
                'description'        => 'Application for test purposes',
                'info'               => '',
                'isInstallable'      => TRUE,
                'key'                => 'null-key',
                'logo'               => NULL,
                'name'               => 'Null',
            ],
            $this->application->toArray(),
        );
    }

    /**
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
     * @return void
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
