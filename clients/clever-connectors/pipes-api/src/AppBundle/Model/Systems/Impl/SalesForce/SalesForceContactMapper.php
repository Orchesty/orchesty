<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class SalesForceContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce
 */
class SalesForceContactMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE)['data'];

        if (!array_key_exists('Email', $data)) {
            throw new CleverConnectorsException(
                'Missing required Email field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $res = [
            'email' => $data['Email'],
        ];

        if (array_key_exists('FirstName', $data)) {
            $res['first_name'] = $data['FirstName'];
        }

        if (array_key_exists('LastName', $data)) {
            $res['last_name'] = $data['LastName'];
        }

        if (array_key_exists('Id', $data)) {
            $res[CleverFieldsEnum::FOREIGN_ID] = $data['Id'];
        }

        return $dto->setData(json_encode($res));
    }

}