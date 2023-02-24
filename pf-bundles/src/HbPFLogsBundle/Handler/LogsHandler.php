<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFLogsBundle\Handler;

use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\Logs\LogsInterface;
use Hanaboso\PipesFramework\Logs\MongoDbLogs;

/**
 * Class LogsHandler
 *
 * @package Hanaboso\PipesFramework\HbPFLogsBundle\Handler
 */
final class LogsHandler
{

    private LogsInterface $logs;

    /**
     * LogsHandler constructor.
     *
     * @param MongoDbLogs $logsManager
     */
    public function __construct(MongoDbLogs $logsManager)
    {
        $this->logs = $logsManager;
    }

    /**
     * @param GridRequestDto $dto
     * @param int            $timeMargin
     *
     * @return mixed[]
     */
    public function getData(GridRequestDto $dto, int $timeMargin): array
    {
        return $this->logs->getData($dto, $timeMargin);
    }

}
