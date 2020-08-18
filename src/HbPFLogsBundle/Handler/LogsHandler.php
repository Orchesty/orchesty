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
     * @var LogsInterface
     */
    private LogsInterface $logs;

    /**
     * LogsHandler constructor.
     *
     * @param LogsInterface $logs
     */
    public function __construct(LogsInterface $logs)
    {
        $this->logs = $logs;
    }

    /**
     * @param GridRequestDto $dto
     *
     * @return mixed[]
     */
    public function getData(GridRequestDto $dto): array
    {
        return $this->logs->getData($dto);
    }

}
