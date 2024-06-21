<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Authorization\Base;

use Exception;
use Hanaboso\CommonsBundle\Crypt\CryptManager;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\Integration\Application\TestNullApplication;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class BasicApplicationAbstractTest
 *
 * @package PipesPhpSdkTests\Integration\Authorization\Base
 */
#[CoversClass(BasicApplicationAbstract::class)]
final class BasicApplicationAbstractTest extends KernelTestCaseAbstract
{

    /**
     * @var TestNullApplication
     */
    private TestNullApplication $testApp;

    /**
     * @return void
     */
    public function testGetAuthorizationType(): void
    {
        self::assertEquals('basic', $this->testApp->getAuthorizationType());
    }

    /**
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
                                BasicApplicationInterface::PASSWORD => 'passwd',
                                BasicApplicationInterface::TOKEN    => '__token__',
                                BasicApplicationInterface::USER     => 'user',
                            ],
                    ],
                ),
            ],
        );
        $applicationInstall = $this->testApp->saveApplicationForms(
            $applicationInstall,
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    BasicApplicationInterface::PASSWORD => 'new_passwd',
                    BasicApplicationInterface::TOKEN    => '__new_token__',
                    BasicApplicationInterface::USER     => 'new_user',
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
