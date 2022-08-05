<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\SendGrid;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\SendGridApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class SendGridApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\SendGrid
 */
final class SendGridApplicationTest extends DatabaseTestCaseAbstract
{

    /**
     * @var SendGridApplication
     */
    private SendGridApplication $app;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\SendGridApplication::getName
     *
     * @throws Exception
     */
    public function testGetKey(): void
    {
        self::assertEquals('send-grid', $this->app->getName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\SendGridApplication::getPublicName
     *
     * @throws Exception
     */
    public function testGetPublicName(): void
    {
        self::assertEquals('SendGrid Application', $this->app->getPublicName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\SendGridApplication::getDescription
     *
     * @throws Exception
     */
    public function testGetDescription(): void
    {
        self::assertEquals('Send Email With Confidence.', $this->app->getDescription());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\SendGridApplication::isAuthorized
     *
     * @throws Exception
     */
    public function testIsAuthorized(): void
    {
        $appInstall = DataProvider::createApplicationInstall($this->app->getName());
        self::assertFalse($this->app->isAuthorized($appInstall));

        $appInstall->setSettings(
            [ApplicationInterface::AUTHORIZATION_FORM => [SendGridApplication::API_KEY => 'key']],
        );
        self::assertTrue($this->app->isAuthorized($appInstall));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\SendGridApplication::getRequestDto
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\SendGridApplication::isAuthorized
     *
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $appInstall = DataProvider::createApplicationInstall(
            $this->app->getName(),
            'user',
            [ApplicationInterface::AUTHORIZATION_FORM => [SendGridApplication::API_KEY => 'key']],
        );

        $dto = $this->app->getRequestDto(
            new ProcessDto(),
            $appInstall,
            CurlManager::METHOD_POST,
            NULL,
            Json::encode(['foo' => 'bar']),
        );
        self::assertEquals(CurlManager::METHOD_POST, $dto->getMethod());
        self::assertEquals(SendGridApplication::BASE_URL, $dto->getUri(TRUE));
        self::assertEquals(Json::encode(['foo' => 'bar']), $dto->getBody());

        $appInstall = DataProvider::createApplicationInstall($this->app->getName());
        self::expectException(ApplicationInstallException::class);
        $this->app->getRequestDto(new ProcessDto(), $appInstall, CurlManager::METHOD_GET);
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\SendGridApplication::getFormStack
     *
     * @throws Exception
     */
    public function testGetFormStack(): void
    {
        $forms = $this->app->getFormStack()->getForms();
        foreach ($forms as $form) {
            self::assertCount(1, $form->getFields());
        }
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

        $this->app = new SendGridApplication();
    }

}
