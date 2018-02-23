<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\HeadersUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Nette\Utils\Json;

/**
 * Class SalesforceContactMapperAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Mapper
 */
abstract class SalesforceContactMapperAbstract implements CustomNodeInterface
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
     * HubspotSyncContactMapper constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData(), TRUE);

        if (!is_array($data) || !array_key_exists('Email', $data)) {
            throw new CleverConnectorsException(
                'Missing data or required field email',
                CleverConnectorsException::MISSING_DATA
            );
        }

        if (!$this->checkDate($data)) {
            return HeadersUtils::setStopHeaderToDto($dto);
        }

        $subscriber = (new CMSubscriber())->setEmail($data['Email']);

        if (array_key_exists('FirstName', $data)) {
            $subscriber->setFirstName($data['FirstName']);
        }

        if (array_key_exists('LastName', $data)) {
            $subscriber->setLastName($data['LastName']);
        }

        if (array_key_exists('Id', $data)) {
            $subscriber->setForeignId($data['Id']);
        }

        if ($this->includeList) {
            $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
            $sett          = $systemInstall->getSettings();
            $subscriber->setLists([$sett[SystemInstall::SELECT_LIST] ?? NULL]);
        }

        return $dto->setData(Json::encode($subscriber->toArray()));
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected abstract function checkDate(array $data): bool;

}