<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class PipedriveSyncPersonMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper
 */
class PipedriveSyncPersonMapper extends PipedrivePersonMapperAbstract
{

    /**
     * @var bool
     */
    protected $includeList = TRUE;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    protected $systemInstallRepository;

    /**
     * PipedrivePersonMapperAbstract constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @param array $data
     *
     * @return array|string
     */
    protected function getInnerData(array $data)
    {
        return $data;
    }

}