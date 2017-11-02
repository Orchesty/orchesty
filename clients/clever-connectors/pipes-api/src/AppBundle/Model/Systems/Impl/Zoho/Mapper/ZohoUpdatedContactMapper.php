<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class ZohoUpdatedContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Mapper
 */
class ZohoUpdatedContactMapper implements CustomNodeInterface
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

        if (!is_array($data)
            || !array_key_exists('FL', $data)
            || empty($data['FL'])
        ) {
            throw new CleverConnectorsException(
                'Malformed or missing data in ZOHO update mapper.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $datas   = [];
        $desired = ['First Name', 'Last Name', 'Email', 'CONTACTID'];
        $search  = 0;

        foreach ($data['FL'] as $row) {
            if (array_key_exists('val', $row)
                && array_key_exists('content', $row)
                && in_array($row['val'], $desired)
            ) {
                $datas[$row['val']] = $row['content'];
                if (++$search === 4) {
                    break;
                }
            }
        }

        if (!array_key_exists('Email', $datas)
            || !array_key_exists('CONTACTID', $datas)
        ) {
            throw new CleverConnectorsException(
                'Missing required email/id field in data, ZOHO update mapper.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $obj = new CMSubscriber();
        $obj->setEmail($datas['Email'])
            ->setForeignId($datas['CONTACTID'] ?? '')
            ->setFirstName($datas['First Name'] ?? '')
            ->setLastName($datas['Last Name'] ?? '');

        return $dto->setData(json_encode($obj->toArray()));
    }

}