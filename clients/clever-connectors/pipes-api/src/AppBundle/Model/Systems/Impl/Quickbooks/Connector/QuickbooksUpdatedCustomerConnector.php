<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/23/17
 * Time: 11:19 AM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Utils\CronUtils;
use DateTime;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

/**
 * Class QuickbooksUpdatedCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector
 */
class QuickbooksUpdatedCustomerConnector extends QuickbooksCustomerConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'quickbooks-updated-customer-connector';
    }

    /**
     * @param SystemInstall $systemInstall
     * @param ProcessDto    $dto
     *
     * @return string
     */
    private function getTimeQuery(SystemInstall $systemInstall, ProcessDto $dto): string
    {
        $lastSync = $this->lastSyncManager->getLastSync($systemInstall, $dto->getHeaders());
        $times    = CronUtils::getTimes($lastSync);

        $since = $times->getStart() ? sprintf(' AND MetaData.LastUpdatedTime >= \'%s\'',
            $times->getStart()->format(DateTime::ATOM)) : '';
        $till  = sprintf(' AND MetaData.LastUpdatedTime < \'%s\'', $times->getEnd()->format(DateTime::ATOM));

        return $since . $till;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param ProcessDto    $dto
     *
     * @return string
     */
    protected function getTotalQuery(SystemInstall $systemInstall, ProcessDto $dto): string
    {
        return 'SELECT COUNT(*) FROM customer WHERE Active = true' . $this->getTimeQuery($systemInstall, $dto);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param ProcessDto    $dto
     * @param int           $start
     * @param int           $count
     *
     * @return string
     */
    protected function getDataQuery(SystemInstall $systemInstall, ProcessDto $dto, int $start, int $count): string
    {
        return sprintf('SELECT * FROM customer WHERE Active = true' . $this->getTimeQuery($systemInstall, $dto)
            . ' STARTPOSITION %d MAXRESULTS %d', $start, $count);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return SystemInstall
     */
    protected function getSystemInstall(ProcessDto $dto): SystemInstall
    {
        return CronUtils::getSystemInstall($dto);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param ProcessDto    $dto
     */
    protected function afterFetch(SystemInstall $systemInstall, ProcessDto $dto): void
    {
        $lastSync = $this->lastSyncManager->getLastSync($systemInstall, $dto->getHeaders());
        $times    = CronUtils::getTimes($lastSync);
        $lastSync->setTimestamp($times->getEnd());
        $this->lastSyncManager->updateLastSync($lastSync);
    }

}