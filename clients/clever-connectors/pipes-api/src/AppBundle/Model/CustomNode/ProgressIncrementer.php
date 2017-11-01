<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: lukas.hlavac
 * Date: 10/27/17
 * Time: 3:55 PM
 */

namespace CleverConnectors\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class ProgressIncrementer
 *
 * @package CleverConnectors\AppBundle\Model\CustomNode
 */
class ProgressIncrementer implements CustomNodeInterface
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
     *
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $processId = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders());

        if (!$processId) {
            throw new CleverConnectorsException(
                'Process ID not found.',
                CleverConnectorsException::PROCESS_ID_NOT_FOUND
            );
        }

        $this->progressCounterService->increment($processId);

        return $dto;
    }

}