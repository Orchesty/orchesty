<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Aim\AimSystem;
use CleverConnectors\AppBundle\Utils\HeadersUtils;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class AimDestinationMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Mapper
 */
abstract class AimDestinationMapperAbstract implements CustomNodeInterface
{

    /**
     * @var string
     */
    private $destination;

    /**
     * @param string $destination
     */
    public function __construct(string $destination)
    {
        $this->destination = $destination;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if (!is_array($data) ||
            !array_key_exists(AimSystem::DATA_KEY_DESTINATIONS, $data) ||
            !is_array($data[AimSystem::DATA_KEY_DESTINATIONS])
        ) {
            throw new CleverConnectorsException(
                sprintf('Missing required "%s" field', AimSystem::DATA_KEY_DESTINATIONS),
                CleverConnectorsException::MISSING_DATA
            );
        }

        if ($this->shouldSkip($data)) {
            return HeadersUtils::setStopHeaderToDto($dto);
        }

        return $dto;
    }

    /**
     * @param $data
     *
     * @return bool
     */
    private function shouldSkip($data): bool
    {
        foreach ($data[AimSystem::DATA_KEY_DESTINATIONS] as $dest) {
            if ($dest === $this->destination || $dest === AimSystem::DESTINATION_ALL) {
                return FALSE;
            }
        }

        return TRUE;
    }
}
