<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Bigcommerce;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Bigcommerce\BigcommerceApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class BigcommerceApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Bigcommerce
 */
final class BigcommerceApplicationTest extends DatabaseTestCaseAbstract
{

    private const CLIENT_ID     = '1t22ea9p6iyih**********rq9o3xf';
    private const CLIENT_SECRET = '811a14ca490bbb2cd188cf4bd9bef795b35c9**********737e5b805038fecb4';

    /**
     * @var BigcommerceApplication
     */
    private $application;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->mockRedirect(BigcommerceApplication::BIGCOMMERCE_URL, self::CLIENT_ID, 'store_v2_products');
        $this->application = self::$container->get('hbpf.application.bigcommerce');
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
            'http://127.0.0.11:8000/applications/authorize/token'
        );

        $this->pf($applicationInstall);
        $this->assertEquals(TRUE, $this->application->isAuthorized($applicationInstall));
        $this->application->authorize($applicationInstall);

    }

    /**
     * @throws Exception
     */
    public function testIsAuthorizedNoToken(): void
    {
        $bigcommerceApplication = $this->application;
        $applicationInstall     = new ApplicationInstall();
        $this->pf($applicationInstall);
        $this->assertEquals(FALSE, $bigcommerceApplication->isAuthorized($applicationInstall));
    }

    /**
     * @throws Exception
     */
    public function testRequestDto(): void
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
            '{"data":"hello data"}'
        );
        $this->assertInstanceOf(RequestDto::class, $dto);
        $this->assertEquals('{"data":"hello data"}', $dto->getBody());
    }

    /**
     *
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(
            ApplicationTypeEnum::CRON,
            $this->application->getApplicationType()
        );
    }

    /**
     *
     */
    public function testName(): void
    {
        self::assertEquals(
            'Bigcommerce',
            $this->application->getName()
        );
    }

    /**
     *
     */
    public function testGetDescription(): void
    {
        self::assertEquals(
            'Bigcommerce v1',
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
            self::assertContains($field->getKey(), ['client_id', 'client_secret']);
        }

    }

}
