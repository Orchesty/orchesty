<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\UsageStats\Enum\EventTypeEnum;

/**
 * Class AppInstallUsageStatsSender
 *
 * @package Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager
 */
class AppInstallUsageStatsSender extends SenderAbstract
{

    /**
     * AppInstallUsageStatsSender constructor.
     *
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curlManager
     */
    public function __construct(DocumentManager $dm, CurlManagerInterface $curlManager)
    {
        parent::__construct(
            $dm,
            $curlManager,
            [EventTypeEnum::INSTALL->value, EventTypeEnum::UNINSTALL->value],
        );
    }

}
