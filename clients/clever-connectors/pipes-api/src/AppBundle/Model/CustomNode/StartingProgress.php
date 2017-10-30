<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stanislav.kundrat
 * Date: 10/12/17
 * Time: 4:55 PM
 */

namespace CleverConnectors\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\ProgressCounter\ProgressCounterService;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class StartingProgress
 *
 * @package CleverConnectors\AppBundle\Model\CustomNode
 */
class StartingProgress implements CustomNodeInterface
{

    /**
     * @var ProgressCounterService
     */
    private $progressCounterService;

    /**
     * StartingProgress constructor.
     *
     * @param ProgressCounterService $progressCounterService
     */
    public function __construct(ProgressCounterService $progressCounterService)
    {
        $this->progressCounterService = $progressCounterService;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $progressId = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders());

        if (!$progressId) {
            throw new CleverConnectorsException(
                'Process ID not found.',
                CleverConnectorsException::PROCESS_ID_NOT_FOUND
            );
        }

        $data   = json_decode($dto->getData(), TRUE);
        $users  = $data['progress_users'] ?? [];
        $groups = $data['progress_groups'] ?? [];

        $this->progressCounterService->start($progressId, 'sync-subscribers', $users, $groups);

        return $dto;
    }

}