<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stanislav.kundrat
 * Date: 10/12/17
 * Time: 4:55 PM
 */

namespace CleverConnectors\AppBundle\Model\CustomNode;

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
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $progressId = $dto->getHeader('progress_id'); // TODO overit s Vencou, ze je v hlavicke progress id
        $data       = json_decode($dto->getData(), TRUE)['data'];
        $users      = $data['progress_users'] ?? [];
        $groups     = $data['progress_groups'] ?? [];

        $this->progressCounterService->start($progressId, $users, $groups);

        return $dto;
    }

}