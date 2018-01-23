<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class ZapierCreateSubscriberMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Mapper
 */
class ZapierUpdateSubscriberMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $fields = [];

        $data = json_decode($dto->getData(), TRUE);
        if (array_key_exists(CleverFieldsEnum::EMAIL, $data)) {
            $fields['email'] = $data[CleverFieldsEnum::EMAIL];
        }
        if (array_key_exists(CleverFieldsEnum::FIRST_NAME, $data)) {
            $fields['first_name'] = $data[CleverFieldsEnum::FIRST_NAME];
        }
        if (array_key_exists(CleverFieldsEnum::LAST_NAME, $data)) {
            $fields['last_name'] = $data[CleverFieldsEnum::LAST_NAME];
        }
        if (array_key_exists(CleverFieldsEnum::FOREIGN_ID, $data)) {
            $fields['id'] = $data[CleverFieldsEnum::FOREIGN_ID];
        }
        if (array_key_exists(CleverFieldsEnum::UNSUBSCRIBE, $data)) {
            $fields[CleverFieldsEnum::UNSUBSCRIBE] = $data[CleverFieldsEnum::UNSUBSCRIBE];
        }
        if (array_key_exists(CleverFieldsEnum::HARD_BOUNCE, $data)) {
            $fields[CleverFieldsEnum::HARD_BOUNCE] = $data[CleverFieldsEnum::HARD_BOUNCE];
        }

        return $dto->setData(json_encode($fields));
    }

}