<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\OAuth2\CustomNode;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\CustomNode\GetApplicationForRefreshBatch;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use React\EventLoop\Factory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class GetApplicationForRefreshBatchTest
 *
 * @package Tests\Integration\Model\Application\Impl\OAuth2\Connector
 */
class GetApplicationForRefreshBatchTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws MongoDBException
     * @throws DateTimeException
     */
    public function testProcessBatch(): void
    {
        $app = new ApplicationInstall();
        $app->setExpires(DateTimeUtils::getUtcDateTime());
        $this->dm->persist($app);
        $this->dm->flush();

        $getAppForRefreshBatchCreateContactConnector = new GetApplicationForRefreshBatch(
            $this->dm
        );

        $loop = Factory::create();

        $dto = new ProcessDto();

        $getAppForRefreshBatchCreateContactConnector->processBatch(
            $dto,
            $loop,
            function (): void {
            }
        )->then(
            function (): void {
                self::assertTrue(TRUE);
            },
            function (): void {
                self::fail('Something gone wrong!');
            }
        );
        $loop->run();

    }

}
