<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class ShopifyCreateCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Mapper
 */
class ShopifyCreateCustomerMapper implements CustomNodeInterface
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
            'customer' => [
                'email'      => $data[CleverFieldsEnum::EMAIL] ?? '',
                'first_name' => $data[CleverFieldsEnum::FIRST_NAME],
                'last_name'  => $data[CleverFieldsEnum::LAST_NAME],
            ],
        ];

        return $dto->setData(json_encode($contact));
    }

}