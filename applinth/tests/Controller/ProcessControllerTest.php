<?php declare(strict_types=1);

namespace ApplinthTests\Controller;

use ApplinthTests\ControllerTestCaseAbstract;
use Exception;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\Utils\Date\DateTimeUtils;

/**
 * Class ProcessControllerTest
 *
 * @package ApplinthTests\Controller
 */
final class ProcessControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetOverview(): void
    {
        $this->createProgress();
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/ProcessController/getOverviewRequest.json',
            [
                'correlationId' => '62d7dce9d86cf603c007f093',
            ],
        );
    }

    /**
     * @throws Exception
     */
    private function createProgress(): void
    {
        $progress = new TopologyProgress();
        $progress
            ->setTopologyId('123456789')
            ->setTotal(10)
            ->setOk(5)
            ->setProcessedCount(5)
            ->setStartedAt(DateTimeUtils::getUtcDateTime('2022-06-14T09:04:58.789Z'))
            ->setFinishedAt(DateTimeUtils::getUtcDateTime('2022-06-14T09:04:59.707Z')->modify('+ 10 second'))
            ->setUser('endUser');
        $this->setProperty($progress, 'id', uniqid());

        $this->pfd($progress);
    }

}
