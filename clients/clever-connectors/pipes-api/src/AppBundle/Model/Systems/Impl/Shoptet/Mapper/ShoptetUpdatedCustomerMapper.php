<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shoptet\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
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

        // todo ...
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $sett          = $systemInstall->getSettings();
        $obj->setLists([$sett[SystemInstall::SELECT_LIST] ?? NULL]);

        return $dto->setData(json_encode($obj->toArray()));
    }

    /**
     * @param array $haystack
     *
     * @return string
     * @throws CleverConnectorsException
     */
    private function getValue(array $haystack): string
    {
        if (!array_key_exists('#', $haystack)) {
            throw new CleverConnectorsException(
                'Missing [#] key in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $haystack['#'];
    }

}