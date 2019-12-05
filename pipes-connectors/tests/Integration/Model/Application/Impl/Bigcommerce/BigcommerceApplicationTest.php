<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Bigcommerce;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Bigcommerce\BigcommerceApplication;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class BigcommerceApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Bigcommerce
 */
final class BigcommerceApplicationTest extends DatabaseTestCaseAbstract
{

    private const CLIENT_ID = '1t22ea9p6iyih**********rq9o3xf';
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
        $bigcommerceApplication->authorize($applicationInstall, ['store_v2_products']);
    }

}
