<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class BasecrmCreateContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
class BasecrmCreateContactMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        $contact = [
            'data' => [
                'email'         => $data[CleverFieldsEnum::EMAIL] ?? '',
                'first_name'    => $data[CleverFieldsEnum::FIRST_NAME] ?? '',
                'last_name'     => $data[CleverFieldsEnum::LAST_NAME] ?? '',
                'custom_fields' => [
                    CleverCustomKeysEnum::UNSUBSCRIBE => $data[CleverFieldsEnum::UNSUBSCRIBE] ?? FALSE,
                    CleverCustomKeysEnum::HARD_BOUNCE => $data[CleverFieldsEnum::HARD_BOUNCE] ?? FALSE,
                ],
            ],
        ];

        return $dto->setData(json_encode($contact));
    }

}