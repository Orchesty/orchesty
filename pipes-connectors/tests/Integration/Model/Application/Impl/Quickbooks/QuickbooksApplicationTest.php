<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Quickbooks;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Quickbooks\QuickbooksApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class QuickbooksApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Quickbooks
 */
final class QuickbooksApplicationTest extends DatabaseTestCaseAbstract
{

    private const CLIENT_ID     = 'ABHdM34Qg4ErjQmTJf9ZGbiOuvl31HR**********od2OwhKEM';
    private const CLIENT_SECRET = 'ufQjovNaxZeRftI4uAQ**********UFzWYMdmvh0';

    /**
     * @var QuickbooksApplication
     */
    private $application;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->mockRedirect(QuickbooksApplication::QUICKBOOKS_URL, self::CLIENT_ID, 'com.intuit.quickbooks.accounting');
        $this->application = self::$container->get('hbpf.application.quickbooks');
    }

    /**
     *
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(ApplicationTypeEnum::WEBHOOK, $this->application->getApplicationType());
    }

    /**
     *
     */
    public function testName(): void
    {
        self::assertEquals('Quickbooks', $this->application->getName());
    }

    /**
     *
     */
    public function testGetDescription(): void
    {
        self::assertEquals(
            'Quickbooks v1',
            $this->application->getDescription()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetSettingsForm(): void
    {
        $fields = $this->application->getSettingsForm()->getFields();
        foreach ($fields as $field) {
            self::assertInstanceOf(Field::class, $field);
            self::assertContains(
                $field->getKey(),
                [
                    'app_id',
                    OAuth2ApplicationInterface::CLIENT_ID,
                    OAuth2ApplicationInterface::CLIENT_SECRET,
                ]
            );
        }

    }

    /**
     * @throws Exception
     */
    public function testAutorize(): void
    {
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $this->application->getKey(),
            'user',
            'token',
            self::CLIENT_ID,
            self::CLIENT_SECRET
        );
        $this->application->setFrontendRedirectUrl(
            $applicationInstall,
            'http://localhost/applications/authorize/token'
        );
        $this->pf($applicationInstall);
        $this->assertEquals(TRUE, $this->application->isAuthorized($applicationInstall));
        $this->application->authorize($applicationInstall, ['com.intuit.quickbooks.accounting']);
    }

    /**
     * @throws Exception
     */
    public function testIsAuthorizedNoToken(): void
    {
        $applicationInstall = new ApplicationInstall();
        $this->pf($applicationInstall);
        $this->assertEquals(FALSE, $this->application->isAuthorized($applicationInstall));
    }

    /**
     * @throws CurlException
     * @throws ApplicationInstallException
     */
    public function testGetRequestDto(): void
    {
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $this->application->getKey(),
            'user',
            'token',
            self::CLIENT_ID,
            self::CLIENT_SECRET
        );

        $this->pf($applicationInstall);
        $dto = $this->application->getRequestDto(
            $applicationInstall,
            'POST',
            'url',
            '{"data":"oooo"}'
        );

        $this->assertEquals('{"data":"oooo"}', $dto->getBody());
        $this->assertEquals('POST', $dto->getMethod());
    }

}
