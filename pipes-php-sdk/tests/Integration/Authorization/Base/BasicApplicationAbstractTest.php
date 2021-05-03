<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Authorization\Base;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use PipesPhpSdkTests\Integration\Application\TestNullApplication;

/**
 * Class BasicApplicationAbstractTest
 *
 * @package PipesPhpSdkTests\Integration\Authorization\Base
 */
final class BasicApplicationAbstractTest extends DatabaseTestCaseAbstract
{

    /**
     * @var TestNullApplication
     */
    private TestNullApplication $testApp;

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract::getAuthorizationType
     */
    public function testGetAuthorizationType(): void
    {
        self::assertEquals('basic', $this->testApp->getAuthorizationType());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract::isAuthorized
     *
     * @throws Exception
     */
    public function testIsAuthorize(): void
    {
        $applicationInstall = $this->createApplicationInstall();
        self::assertFalse($this->testApp->isAuthorized($applicationInstall));

        $applicationInstall = $this->createApplicationInstall(
            [ApplicationInterface::AUTHORIZATION_SETTINGS => [BasicApplicationInterface::PASSWORD => 'just_password']],
        );
        self::assertFalse($this->testApp->isAuthorized($applicationInstall));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract::setApplicationPassword
     *
     * @throws Exception
     */
    public function testSetApplicationPassword(): void
    {
        $applicationInstall = $this->createApplicationInstall(
            [ApplicationInterface::AUTHORIZATION_SETTINGS => [BasicApplicationInterface::PASSWORD => 'just_password']],
        );

        $applicationInstall = $this->testApp->setApplicationPassword($applicationInstall, 'very_secret_password');
        self::assertEquals(
            'very_secret_password',
            $applicationInstall->getSettings(
            )[ApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::PASSWORD],
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract::setApplicationUser
     *
     * @throws Exception
     */
    public function testSetApplicationUser(): void
    {
        $applicationInstall = $this->createApplicationInstall(
            [ApplicationInterface::AUTHORIZATION_SETTINGS => [BasicApplicationInterface::USER => 'just_user']],
        );
        $applicationInstall = $this->testApp->setApplicationUser($applicationInstall, 'new_user');

        self::assertEquals(
            'new_user',
            $applicationInstall->getSettings(
            )[ApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::USER],
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract::setApplicationToken
     *
     * @throws Exception
     */
    public function testSetApplicationToken(): void
    {
        $applicationInstall = $this->createApplicationInstall(
            [ApplicationInterface::AUTHORIZATION_SETTINGS => [BasicApplicationInterface::TOKEN => '__token__']],
        );
        $applicationInstall = $this->testApp->setApplicationToken($applicationInstall, '__new_token__');

        self::assertEquals(
            '__new_token__',
            $applicationInstall->getSettings(
            )[ApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::TOKEN],
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract::setApplicationSettings
     *
     * @throws Exception
     */
    public function testSetApplicationSettings(): void
    {
        $applicationInstall = $this->createApplicationInstall(
            [
                ApplicationAbstract::FORM =>
                    [
                        ApplicationInterface::AUTHORIZATION_SETTINGS =>
                            [
                                BasicApplicationInterface::USER     => 'user',
                                BasicApplicationInterface::PASSWORD => 'passwd',
                                BasicApplicationInterface::TOKEN    => '__token__',
                            ],
                    ],
            ],
        );
        $applicationInstall = $this->testApp->setApplicationSettings(
            $applicationInstall,
            [
                BasicApplicationInterface::TOKEN    => '__new_token__',
                BasicApplicationInterface::USER     => 'new_user',
                BasicApplicationInterface::PASSWORD => 'new_passwd',
            ],
        );

        self::assertEquals(
            '__new_token__',
            $applicationInstall->getSettings(
            )[ApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::TOKEN],
        );
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->testApp = new TestNullApplication();
    }

    /**
     * @param mixed[] $settings
     *
     * @return ApplicationInstall
     * @throws Exception
     */
    private function createApplicationInstall(array $settings = []): ApplicationInstall
    {
        $applicationInstall = (new ApplicationInstall())
            ->setKey('null-key')
            ->setUser('user')
            ->setSettings($settings);

        $this->pfd($applicationInstall);

        return $applicationInstall;
    }

}
