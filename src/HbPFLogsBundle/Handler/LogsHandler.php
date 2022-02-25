<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFLogsBundle\Handler;

use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\Logs\LogsInterface;

/**
 * Class LogsHandler
 *
 * @package Hanaboso\PipesFramework\HbPFLogsBundle\Handler
 */
final class LogsHandler
{

    /**
     * LogsHandler constructor.
     *
     * @param LogsInterface $logs
     */
    public function __construct(private LogsInterface $logs)
    {
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
