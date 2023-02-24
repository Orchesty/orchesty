<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Bigcommerce;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Bigcommerce\BigcommerceApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;

/**
 * Class BigcommerceApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Bigcommerce
 */
final class BigcommerceApplicationTest extends KernelTestCaseAbstract
{

    private const CLIENT_ID     = '1t22ea9p6iyih**********rq9o3xf';
    private const CLIENT_SECRET = '811a14ca490bbb2cd188cf4bd9bef795b35c9**********737e5b805038fecb4';

    /**
     * @var BigcommerceApplication
     */
    private BigcommerceApplication $application;

    /**
     * @throws Exception
     */
    public function testAutorize(): void
    {
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $this->application->getName(),
            'user',
            'token',
            self::CLIENT_ID,
            self::CLIENT_SECRET,
        );
        $this->application->setFrontendRedirectUrl(
            $applicationInstall,
            'http://127.0.0.11:8000/applications/authorize/token',
        );

        self::assertTrue($this->application->isAuthorized($applicationInstall));
        $this->application->authorize($applicationInstall);
    }

    /**
     * @throws Exception
     */
    public function testIsAuthorizedNoToken(): void
    {
        $bigcommerceApplication = $this->application;
        $applicationInstall     = new ApplicationInstall();
        self::assertEquals(FALSE, $bigcommerceApplication->isAuthorized($applicationInstall));
    }

    /**
     * @throws Exception
     */
    public function testRequestDto(): void
    {
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $this->application->getName(),
            'user',
            'token',
            self::CLIENT_ID,
            self::CLIENT_SECRET,
        );
        $dto                = $this->application->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            'POST',
            'url',
            '{"data":"hello data"}',
        );
        self::assertEquals('{"data":"hello data"}', $dto->getBody());
    }

    /**
     *
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(
            ApplicationTypeEnum::CRON->value,
            $this->application->getApplicationType(),
        );
    }

    /**
     *
     */
    public function testPublicName(): void
    {
        self::assertEquals(
            'Bigcommerce',
            $this->application->getPublicName(),
        );
    }

    /**
     *
     */
    public function testGetDescription(): void
    {
        self::assertEquals(
            'Bigcommerce v1',
            $this->application->getDescription(),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetFormStack(): void
    {
        $forms = $this->application->getFormStack()->getForms();
        foreach ($forms as $form) {
            foreach ($form->getFields() as $field) {
                self::assertContains($field->getKey(), ['client_id', 'client_secret']);
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRedirect(BigcommerceApplication::BIGCOMMERCE_URL, self::CLIENT_ID, 'store_v2_products');
        $this->application = self::getContainer()->get('hbpf.application.bigcommerce');
    }

}
