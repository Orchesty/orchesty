<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\OAuth2\CustomNode;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\CustomNode\GetApplicationForRefreshBatch;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use Hanaboso\Utils\Date\DateTimeUtils;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;

/**
 * Class GetApplicationForRefreshBatchTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\OAuth2\CustomNode
 */
final class GetApplicationForRefreshBatchTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\CustomNode\GetApplicationForRefreshBatch::processBatch
     * @throws Exception
     */
    public function testProcessBatch(): void
    {
        $this->pfd((new ApplicationInstall())->setExpires(DateTimeUtils::getUtcDateTime()));

        $this->assertBatch(
            self::$container->get('hbpf.custom_node.get_application_for_refresh_batch'),
            new ProcessDto(),
            static function (SuccessMessage $successMessage): void {
                self::assertEquals('', $successMessage->getData());
            }
        );
    }

    /**
     * @throws ConnectorException
     */
    public function testProcess(): void
    {
        $getAppForRefreshBatchCreateContactConnector = new GetApplicationForRefreshBatch(
            $this->dm
        );
        $this->expectException(ConnectorException::class);
        $getAppForRefreshBatchCreateContactConnector->process(new ProcessDto());
    }

}
