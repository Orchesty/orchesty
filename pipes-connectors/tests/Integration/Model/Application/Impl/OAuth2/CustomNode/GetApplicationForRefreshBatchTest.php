<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\OAuth2\CustomNode;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\CustomNode\GetApplicationForRefreshBatch;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class GetApplicationForRefreshBatchTest
 *
 * @package Tests\Integration\Model\Application\Impl\OAuth2\CustomNode
 */
class GetApplicationForRefreshBatchTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\CustomNode\GetApplicationForRefreshBatch::processBatch
     * @throws Exception
     */
    public function testProcessBatch(): void
    {
        $this->pf((new ApplicationInstall())->setExpires(DateTimeUtils::getUtcDateTime()));

        $this->assertBatch(
            self::$container->get('hbpf.custom_node.get_application_for_refresh_batch'),
            new ProcessDto(),
            function (SuccessMessage $successMessage): void {
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
