<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs\Manager;

use Hanaboso\PipesFramework\Logs\LogsAbstract;
use LogicException;

/**
 * Class LogsManagerLoader
 *
 * @package Hanaboso\PipesFramework\Logs\Manager
 */
final class LogsManagerLoader
{

    /**
     * LogsManagerLoader constructor.
     *
     * @param string            $logsService
     * @param LogsAbstract|null $elasticsLogsManager
     * @param LogsAbstract|null $mongoLogsManager
     */
    public function __construct(
        private string $logsService,
        private ?LogsAbstract $elasticsLogsManager,
        private ?LogsAbstract $mongoLogsManager,
    )
    {
    }

    /**
     * @return LogsAbstract
     */
    public function getManager(): LogsAbstract
    {
        switch ($this->logsService) {
            case 'mongo':
                if (!$this->mongoLogsManager){
                    throw new LogicException('Mongo manager is not set in container.');
                }

                return $this->mongoLogsManager;
            case 'elastic':
                if (!$this->elasticsLogsManager) {
                    throw new LogicException('Elastic manager is not set in container.');
                }

                return $this->elasticsLogsManager;
            default:
                throw new LogicException(
                    sprintf('[%s] is not a valid option for logs manager.', $this->logsService),
                );
        }
    }

}
