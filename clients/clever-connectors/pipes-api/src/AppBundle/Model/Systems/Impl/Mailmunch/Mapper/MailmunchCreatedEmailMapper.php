<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Mailmunch\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class MailmunchCreatedEmailMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Mailmunch\Mapper
 */
class MailmunchCreatedEmailMapper implements CustomNodeInterface
{

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * MailmunchCreatedEmailMapper constructor.
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
        $data = json_decode($dto->getData(), TRUE);

        if (!array_key_exists('email', $data)) {
            throw new CleverConnectorsException(
                'Missing required email field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $sys  = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $sett = $sys->getSettings();

        $obj = new CMSubscriber();
        $obj
            ->setEmail($data['email']);

        if (array_key_exists(SystemInstall::SELECT_LIST, $sett)) {
            $obj->setLists([$sett[SystemInstall::SELECT_LIST]]);
        }

        if (array_key_exists('first-name', $data)) {
            $obj->setFirstName($data['first-name']);
        }

        if (array_key_exists('last-name', $data)) {
            $obj->setLastName($data['last-name']);
        }

        return $dto->setData(json_encode($obj->toArray()));
    }

}