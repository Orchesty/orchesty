<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Magento2\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Nette\Utils\Json;

/**
 * Class Magento2UpdateCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Magento2\Mapper
 */
class Magento2UpdateCustomerMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData(), TRUE);

        if (!array_key_exists('email', $data)) {
            throw new CleverConnectorsException(
                'Missing required email field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $res = [
            'email' => $data['email'],
        ];

        if (array_key_exists('firstname', $data)) {
            $res['first_name'] = $data['firstname'];
        }
        if (array_key_exists('lastname', $data)) {
            $res['last_name'] = $data['lastname'];
        }

        return $dto->setData(Json::encode($res));
    }

}