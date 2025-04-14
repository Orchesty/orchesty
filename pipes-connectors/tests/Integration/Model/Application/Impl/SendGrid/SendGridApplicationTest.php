<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\SendGrid;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\SendGrid\SendGridApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class SendGridApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\SendGrid
 */
#[CoversClass(SendGridApplication::class)]
final class SendGridApplicationTest extends KernelTestCaseAbstract
{

    /**
     * @var SendGridApplication
     */
    private SendGridApplication $app;

    /**
     * @throws Exception
     */
    public function testGetKey(): void
    {
        self::assertSame('send-grid', $this->app->getName());
    }

    /**
     * @throws Exception
     */
    public function testGetPublicName(): void
    {
        self::assertSame('SendGrid Application', $this->app->getPublicName());
    }

    /**
     * @throws Exception
     */
    public function testGetDescription(): void
    {
        self::assertSame('Send Email With Confidence.', $this->app->getDescription());
    }

    /**
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
        self::assertSame(CurlManager::METHOD_POST, $dto->getMethod());
        self::assertEquals(SendGridApplication::BASE_URL, $dto->getUri(TRUE));
        self::assertSame(Json::encode(['foo' => 'bar']), $dto->getBody());

        $appInstall = DataProvider::createApplicationInstall($this->app->getName());
        self::expectException(ApplicationInstallException::class);
        $this->app->getRequestDto(new ProcessDto(), $appInstall, CurlManager::METHOD_GET);
    }

    /**
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
