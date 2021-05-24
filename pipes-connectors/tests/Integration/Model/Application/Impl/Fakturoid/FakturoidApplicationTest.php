<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid;

use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\FakturoidApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;

/**
 * Class FakturoidApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid
 */
final class FakturoidApplicationTest extends DatabaseTestCaseAbstract
{

    /**
     * @var FakturoidApplication
     */
    private FakturoidApplication $app;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\FakturoidApplication::getKey
     *
     * @throws Exception
     */
    public function testGetKey(): void
    {
        self::assertEquals('fakturoid', $this->app->getKey());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\FakturoidApplication::getName
     *
     * @throws Exception
     */
    public function testGetName(): void
    {
        self::assertEquals('Fakturoid aplication', $this->app->getName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\FakturoidApplication::getDescription
     *
     * @throws Exception
     */
    public function testGetDescription(): void
    {
        self::assertEquals('Fakturoid aplication', $this->app->getDescription());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\FakturoidApplication::getSettingsForm
     *
     * @throws Exception
     */
    public function testGetSettingsForm(): void
    {
        $form = $this->app->getSettingsForm();
        self::assertCount(3, $form->getFields());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\FakturoidApplication::isAuthorized
     *
     * @throws Exception
     */
    public function testIsAuthorized(): void
    {
        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_SETTINGS => [
                    BasicApplicationInterface::USER     => 'hana******.com',
                    BasicApplicationInterface::PASSWORD => 'cf4*****191bbef40dcd86*****625ec4c4*****',
                ],
                ApplicationAbstract::FORM                    => [
                    FakturoidApplication::ACCOUNT => 'test',
                ],
            ],
        );
        self::assertTrue($this->app->isAuthorized($applicationInstall));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\FakturoidApplication::getRequestDto
     *
     * @throws Exception
     */
    public function testGetRequestDtoWithData(): void
    {
        $app                = self::$container->get('hbpf.application.fakturoid');
        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_SETTINGS => [
                    BasicApplicationInterface::USER     => 'hana******.com',
                    BasicApplicationInterface::PASSWORD => 'cf4*****191bbef40dcd86*****625ec4c4*****',
                ],
                ApplicationAbstract::FORM                    => [
                    FakturoidApplication::ACCOUNT => 'test',
                ],
            ],
        );
        $app->getRequestDto(
            $applicationInstall,
            CurlManager::METHOD_POST,
            'https://app.fakturoid.cz/api/v2',
            '{
                                "subdomain": "fakturacnitest"
                                }',
        );
        self::assertFake();
    }

    /**
     * -------------------------------------------- HELPERS ------------------------------------
     */

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app = self::$container->get('hbpf.application.fakturoid');
    }

}
