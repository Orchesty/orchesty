<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Authorization\Base;

use Exception;
use Hanaboso\CommonsBundle\Crypt\CryptManager;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use PipesPhpSdkTests\Integration\Application\TestNullApplication;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class BasicApplicationAbstractTest
 *
 * @package PipesPhpSdkTests\Integration\Authorization\Base
 */
final class BasicApplicationAbstractTest extends KernelTestCaseAbstract
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
        $applicationInstall = new ApplicationInstall();
        self::assertFalse($this->testApp->isAuthorized($applicationInstall));

        /** @var CryptManager $cryptManager */
        $cryptManager       = self::getContainer()->get('hbpf.commons.crypt.crypt_manager');
        $applicationInstall = new ApplicationInstall(
            [
                'encryptedSettings' => $cryptManager->encrypt(
                    [ApplicationInterface::AUTHORIZATION_FORM => [BasicApplicationInterface::PASSWORD => 'just_password']],
                ),
            ],
        );
        self::assertFalse($this->testApp->isAuthorized($applicationInstall));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract::saveApplicationForms
     *
     * @throws Exception
     */
    public function testSetApplicationToken(): void
    {
        /** @var CryptManager $cryptManager */
        $cryptManager       = self::getContainer()->get('hbpf.commons.crypt.crypt_manager');
        $applicationInstall = new ApplicationInstall(
            [
                'encryptedSettings' => $cryptManager->encrypt(
                    [ApplicationInterface::AUTHORIZATION_FORM => [ApplicationInterface::TOKEN => '__token__']],
                ),
            ],
        );
        $applicationInstall = $this->testApp->saveApplicationForms(
            $applicationInstall,
            [ApplicationInterface::AUTHORIZATION_FORM => [ApplicationInterface::TOKEN => '__new_token__']],
        );

        self::assertEquals(
            '__new_token__',
            $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN],
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract::saveApplicationForms
     *
     * @throws Exception
     */
    public function testSetApplicationSettings(): void
    {
        /** @var CryptManager $cryptManager */
        $cryptManager       = self::getContainer()->get('hbpf.commons.crypt.crypt_manager');
        $applicationInstall = new ApplicationInstall(
            [
                'encryptedSettings' => $cryptManager->encrypt(
                    [
                        ApplicationInterface::AUTHORIZATION_FORM =>
                            [
                                BasicApplicationInterface::USER     => 'user',
                                BasicApplicationInterface::PASSWORD => 'passwd',
                                BasicApplicationInterface::TOKEN    => '__token__',
                            ],
                    ],
                ),
            ],
        );
        $applicationInstall = $this->testApp->saveApplicationForms(
            $applicationInstall,
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    BasicApplicationInterface::TOKEN    => '__new_token__',
                    BasicApplicationInterface::USER     => 'new_user',
                    BasicApplicationInterface::PASSWORD => 'new_passwd',
                ],
            ],
        );

        self::assertEquals(
            '__new_token__',
            $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN],
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

}
