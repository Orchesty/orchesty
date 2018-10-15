<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/24/17
 * Time: 10:30 AM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class QuickbooksCreatedCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Mapper
 */
class QuickbooksCreatedCustomerMapper extends QuickbooksUpdatedCustomerMapper
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
     * QuickbooksCreatedCustomerMapper constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

}