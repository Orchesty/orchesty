<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/24/17
 * Time: 10:30 AM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class QuickbooksUpdatedCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Mapper
 */
class QuickbooksUpdatedCustomerMapper implements CustomNodeInterface
{

    /**
     * @var bool
     */
    protected $includeList = FALSE;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    protected $systemInstallRepository;

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        return $this->processData($data, $dto);
    }

    /**
     * @param array      $data
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    protected function processData(array $data, ProcessDto $dto): ProcessDto
    {
        if (empty($data)
            || !array_key_exists('PrimaryEmailAddr', $data)
            || !array_key_exists('Address', $data['PrimaryEmailAddr'])
        ) {
            return $this->setHeadersToStop($dto);
        }

        $obj = new CMSubscriber();
        $obj->setEmail($data['PrimaryEmailAddr']['Address']);
        $obj->setForeignId($data['Id']);
        $obj->setReactivate($data['Active']);

        if (array_key_exists('GivenName', $data)) {
            $obj->setFirstName($data['GivenName'] ?? '');
        }

        if (array_key_exists('FamilyName', $data)) {
            $obj->setLastName($data['FamilyName'] ?? '');
        }

        if ($this->includeList) {
            $sys  = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
            $sett = $sys->getSettings();

            if (array_key_exists(SystemInstall::SELECT_LIST, $sett)) {
                $obj->setLists([$sett[SystemInstall::SELECT_LIST]]);
            }
        }

        return $dto->setData(json_encode($obj->toArray()));
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    protected function setHeadersToStop(ProcessDto $dto): ProcessDto
    {
        $headers       = $dto->getHeaders();
        $key           = CMHeaders::createKey(CMHeaders::RESULT_CODE);
        $headers[$key] = 1003;
        $dto->setHeaders($headers);

        return $dto;
    }

}