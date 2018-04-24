<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class BigcommerceCreateCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper
 */
class BigcommerceCreateCustomerMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data   = json_decode($dto->getData(), TRUE);
        $person = [];

        if (empty($data[CleverFieldsEnum::EMAIL] ?? '')) {
            throw new CleverConnectorsException(
                'Missing required email in create event, BigCommerce.'
            );
        }

        $person['email'] = $data[CleverFieldsEnum::EMAIL];
        if (!empty($data[CleverFieldsEnum::FIRST_NAME] ?? '')) {
            $person['first_name'] = $data[CleverFieldsEnum::FIRST_NAME];
        }
        if (!empty($data[CleverFieldsEnum::LAST_NAME] ?? '')) {
            $person['last_name'] = $data[CleverFieldsEnum::LAST_NAME];
        }

        return $dto->setData(json_encode($person));
    }

}