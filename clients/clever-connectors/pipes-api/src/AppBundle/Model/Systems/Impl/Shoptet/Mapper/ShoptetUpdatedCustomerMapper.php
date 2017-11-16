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
        $customer = json_decode($dto->getData(), TRUE);

        if (!is_array($customer)) {
            throw new CleverConnectorsException('Missing fields in data.', CleverConnectorsException::MISSING_DATA);
        }

        $guid  = '';
        $email = '';

        if (array_key_exists('ACCOUNT', $customer) &&
            array_key_exists('EMAIL', $customer['ACCOUNT']) &&
            !empty($customer['ACCOUNT']['EMAIL'])
        ) {
            $account = $customer['ACCOUNT'];
            $email   = $this->getValue($account['EMAIL']);
            if (array_key_exists('GUID', $account)) {
                $guid = $this->getValue($account['GUID']);
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
            $fullName  = explode(' ', $this->getValue($customer['BILLING_ADDRESS']['FULL_NAME']));
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

    /**
     * @param string $key
     * @param array  $haystack
     *
     * @return bool
     */
    private function hasValue(string $key, array $haystack): bool
    {
        return array_key_exists($key, $haystack) && array_key_exists('#', $haystack[$key]);
    }

    /**
     * @param array $haystack
     *
     * @return string
     */
    private function getValue(array $haystack): string
    {
        return $haystack['#'];
    }

}