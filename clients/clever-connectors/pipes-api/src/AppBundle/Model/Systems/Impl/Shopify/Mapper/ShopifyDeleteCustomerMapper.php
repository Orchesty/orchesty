<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class ShopifyDeleteCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Mapper
 */
class ShopifyDeleteCustomerMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if (!array_key_exists('id', $data)) {
            throw new CleverConnectorsException(
                'Missing required id field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }
        $res = [
            CleverFieldsEnum::FOREIGN_ID => (string) $data['id'],
            //TODO Shopify does not send email that is required by cm...
            'email'                      => (string) $data['id'],
        ];

        return $dto->setData(json_encode($res));
    }

}