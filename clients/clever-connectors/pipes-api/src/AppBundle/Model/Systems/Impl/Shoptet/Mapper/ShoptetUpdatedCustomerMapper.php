<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shoptet\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class ShoptetUpdatedCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shoptet\Mapper
 */
class ShoptetUpdatedCustomerMapper implements CustomNodeInterface
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

        if (!is_array($data) || !array_key_exists('CUSTOMER', $data)) {
            throw new CleverConnectorsException(
                'Missing required CUSTOMER field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $customer = $data['CUSTOMER'];
        $guid     = '';
        $email    = '';

        // takes the first email - can be modified to take the main one (if there is one)
        if (array_key_exists('ACCOUNTS', $data['CUSTOMER'])) {
            foreach ($customer['ACCOUNTS'] as $account) {
                if (array_key_exists('EMAIL', $account) && !empty($account['EMAIL'])) {
                    $email = $account['EMAIL'];
                    if (array_key_exists('GUID', $account)) {
                        $guid = $account['GUID'];
                    }
                    break;
                }
            }
        }

        if (empty($email)) {
            throw new CleverConnectorsException(
                'Missing required EMAIL field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $obj = new CMSubscriber();
        $obj->setEmail($email);

        if (array_key_exists('BILLING_ADDRESS', $customer) &&
            array_key_exists('FULL_NAME', $customer['BILLING_ADDRESS'])
        ) {
            $fullName  = explode(' ', $customer['BILLING_ADDRESS']['FULL_NAME']);
            $firstName = $fullName[0] ?? '';
            $lastName  = $fullName[1] ?? '';

            $obj->setFirstName($firstName);
            $obj->setLastName($lastName);
        }

        if (!empty($guid)) {
            $obj->setForeignId($guid);
        }

        return $dto->setData(json_encode($obj->toArray()));
    }

}