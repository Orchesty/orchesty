<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class ZendeskSyncUserMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Mapper
 */
class ZendeskSyncUserMapper extends ZendeskUserMapperAbstract
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
     * ZendeskCreatedUserMapper constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

}