<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class BasecrmCreatedContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
class BasecrmCreatedContactMapper extends BasecrmContactMapperAbstract
{

    /**
     * @var array
     */
    protected static $event_types = ['created'];

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    protected $systemInstallRepository;

    /**
     * BasecrmUpdatedContactMapper constructor.
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

        if ($this->checkEventType($data, $dto)) {
            $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
            $sett          = $systemInstall->getSettings();

            $obj = $this->getSubscriber($data);

            if (array_key_exists(SystemInstall::SELECT_LIST, $sett)) {
                $obj->setLists([$sett[SystemInstall::SELECT_LIST]]);
            }

            $dto->setData(json_encode($obj->toArray()));
        }

        return $dto;
    }

}