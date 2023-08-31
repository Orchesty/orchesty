<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFLogsBundle\Handler;

use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\Logs\LogsInterface;
use Hanaboso\PipesFramework\Logs\Manager\LogsManagerLoader;

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
     * @param LogsManagerLoader $logsLoader
     */
    public function __construct(LogsManagerLoader $logsLoader)
    {
        $this->logs = $logsLoader->getManager();
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
