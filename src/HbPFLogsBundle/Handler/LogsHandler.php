<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/19/18
 * Time: 1:46 PM
 */

namespace Hanaboso\PipesFramework\HbPFLogsBundle\Handler;

use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\Logs\LogsInterface;

/**
 * Class LogsHandler
 *
 * @package Hanaboso\PipesFramework\HbPFLogsBundle\Handler
 */
class LogsHandler
{

    /**
     * @var LogsInterface
     */
    private $logs;

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
     * @return array
     */
    public function getData(GridRequestDto $dto): array
    {
        return $this->logs->getData($dto);
    }

}