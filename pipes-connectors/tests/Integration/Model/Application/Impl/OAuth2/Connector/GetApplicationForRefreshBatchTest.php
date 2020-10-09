<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\OAuth2\Connector;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector\GetApplicationForRefreshBatchConnector;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use Hanaboso\Utils\Date\DateTimeUtils;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;

/**
 * Class GetApplicationForRefreshBatchTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\OAuth2\Connector
 */
final class GetApplicationForRefreshBatchTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector\GetApplicationForRefreshBatchConnector::processBatch
     * @throws Exception
     */
    public function testProcessBatch(): void
    {
        $this->pfd((new ApplicationInstall())->setExpires(DateTimeUtils::getUtcDateTime()));

        $this->assertBatch(
            self::$container->get('hbpf.connector.batch-get_application_for_refresh'),
            new ProcessDto(),
            static function (SuccessMessage $successMessage): void {
                self::assertEquals('', $successMessage->getData());
            }
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector\GetApplicationForRefreshBatchConnector::getId
     */
    public function testGetId(): void
    {
        $application = self::$container->get('hbpf.connector.batch-get_application_for_refresh');

        self::assertEquals('get_application_for_refresh', $application->getId());
    }

    /**
     * @throws ConnectorException
     */
    public function testProcess(): void
    {
        $getAppForRefreshBatchCreateContactConnector = new GetApplicationForRefreshBatchConnector($this->dm);
        $this->expectException(ConnectorException::class);
        $getAppForRefreshBatchCreateContactConnector->processAction(new ProcessDto());
    }

}
