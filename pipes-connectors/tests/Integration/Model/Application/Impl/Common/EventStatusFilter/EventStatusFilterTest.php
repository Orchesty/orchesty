<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Common\EventStatusFilter;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Common\Events\EventEnum;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Common\EventStatusFilter\EventStatusFilter;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;

/**
 * Class EventStatusFilterTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Common\EventStatusFilter
 */
final class EventStatusFilterTest extends DatabaseTestCaseAbstract
{

    /**
     * @return void
     * @throws PipesFrameworkException
     */
    public function testProcessAction(): void
    {
        $eventStatusFilter = new EventStatusFilter(EventEnum::PROCESS_SUCCESS);
        $dto               = new ProcessDto();

        $dto->setJsonData(['type' => EventEnum::PROCESS_SUCCESS]);
        $dto = $eventStatusFilter->processAction($dto);

        self::assertEquals(0, sizeof($dto->getHeaders()));

        $dto->setJsonData(['type' => EventEnum::PROCESS_FAILED]);
        $dto = $eventStatusFilter->processAction($dto);

        self::assertEquals(
            [
                'result-message' => 'Filtered out!',
                'result-code'    => '1003',
            ],
            $dto->getHeaders(),
        );
    }

    /**
     * @return void
     */
    public function testGetName(): void
    {
        $eventStatusFilter = new EventStatusFilter(EventEnum::PROCESS_SUCCESS);
        self::assertEquals(
            'event-status-filter',
            $eventStatusFilter->getName(),
        );
    }

}
