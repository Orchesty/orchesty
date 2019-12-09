<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Bigcommerce;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
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
     * @throws Exception
     */
    public function testAutorize(): void
    {
        $this->mockRedirect(BigcommerceApplication::BIGCOMMERCE_URL, self::CLIENT_ID, 'store_v2_products');
        $bigcommerceApplication = self::$container->get('hbpf.application.bigcommerce');
        $applicationInstall     = DataProvider::getOauth2AppInstall(
            $bigcommerceApplication->getKey(),
            'user',
            'token',
            self::CLIENT_ID,
            self::CLIENT_SECRET
        );
        $bigcommerceApplication->setFrontendRedirectUrl(
            $applicationInstall,
            'http://127.0.0.11:8000/applications/authorize/token'
        );

        $this->pf($applicationInstall);
        $this->assertEquals(TRUE, $bigcommerceApplication->isAuthorized($applicationInstall));
        $bigcommerceApplication->authorize($applicationInstall, ['store_v2_products']);

    }

    /**
     * @throws DateTimeException
     */
    public function testIsAuthorizedNoToken(): void
    {
        $bigcommerceApplication = self::$container->get('hbpf.application.bigcommerce');
        $applicationInstall     = new ApplicationInstall();
        $this->pf($applicationInstall);
        $this->assertEquals(FALSE, $bigcommerceApplication->isAuthorized($applicationInstall));
    }

    /**
     * @throws Exception
     */
    public function testRequestDto(): void
    {
        $bigcommerceApplication = self::$container->get('hbpf.application.bigcommerce');
        $applicationInstall     = DataProvider::getOauth2AppInstall(
            $bigcommerceApplication->getKey(),
            'user',
            'token',
            self::CLIENT_ID,
            self::CLIENT_SECRET
        );
        $this->pf($applicationInstall);
        $dto = $bigcommerceApplication->getRequestDto(
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
        $bigcommerce = self::$container->get('hbpf.application.bigcommerce');
        self::assertEquals(
            ApplicationTypeEnum::WEBHOOK,
            $bigcommerce->getApplicationType()
        );
    }

    /**
     *
     */
    public function testName(): void
    {
        $bigcommerce = self::$container->get('hbpf.application.bigcommerce');
        self::assertEquals(
            'Bigcommerce',
            $bigcommerce->getName()
        );
    }

    /**
     *
     */
    public function testGetDescription(): void
    {
        $bigcommerce = self::$container->get('hbpf.application.bigcommerce');
        self::assertEquals(
            'Bigcommerce v1',
            $bigcommerce->getDescription()
        );
    }

    /**
     *
     */
    public function testGetSettingsForm(): void
    {
        $bigcommerce = self::$container->get('hbpf.application.bigcommerce');

        $fields = $bigcommerce->getSettingsForm()->getFields();
        foreach ($fields as $field) {
            self::assertInstanceOf(Field::class, $field);
            self::assertContains($field->getKey(), ['client_id', 'client_secret']);
        }

    }

}
