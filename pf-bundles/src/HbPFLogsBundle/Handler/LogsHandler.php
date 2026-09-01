<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFLogsBundle\Handler;

use Exception;
use Hanaboso\MongoDataGrid\GridHandlerTrait;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\PipesFramework\Logs\Model\LogsManager;
use Hanaboso\PipesFramework\Logs\MongoDbLogs;

/**
 * Class LogsHandler
 *
 * @package Hanaboso\PipesFramework\HbPFLogsBundle\Handler
 */
final readonly class LogsHandler
{

    use GridHandlerTrait;

    /**
     * LogsHandler constructor.
     *
     * @param LogsManager $manager
     * @param MongoDbLogs $logsManager
     */
    public function __construct(private LogsManager $manager, private MongoDbLogs $logsManager)
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
        return $this->logsManager->getData($dto, $timeMargin);
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getLogs(GridRequestDtoInterface $dto): array
    {
        return $this->getGridResponse($dto, $this->manager->getLogs($dto));
    }

}
