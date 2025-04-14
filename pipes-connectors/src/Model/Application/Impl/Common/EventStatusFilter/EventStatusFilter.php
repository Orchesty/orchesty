<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Common\EventStatusFilter;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract;
use Hanaboso\Utils\Exception\PipesFrameworkException;

/**
 * Class EventStatusFilter
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Common\EventStatusFilter
 */
final class EventStatusFilter extends CommonNodeAbstract
{

    public const string NAME = 'event-status-filter';

    /**
     * EventStatusFilter constructor.
     *
     * @param string                       $type
     * @param ApplicationInstallRepository $applicationInstallRepository
     */
    public function __construct(
        private readonly string $type,
        ApplicationInstallRepository $applicationInstallRepository,
    )
    {
        parent::__construct($applicationInstallRepository);
    }

    /**
     * @return string
     */
    function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws PipesFrameworkException
     */
    function processAction(ProcessDto $dto): ProcessDto
    {
        $data = $dto->getJsonData();
        if ($data['type'] !== $this->type) {
            $dto->setStopProcess(ProcessDto::DO_NOT_CONTINUE, 'Filtered out!');
        }

        return $dto;
    }

}
