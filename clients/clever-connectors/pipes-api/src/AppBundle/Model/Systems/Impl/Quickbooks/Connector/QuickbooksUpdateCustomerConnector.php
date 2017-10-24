<?php
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

class QuickbooksUpdateCustomerConnector extends QuickbooksCustomerConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'quickbooks-update-customer-connector';
    }

    private function getTimeQuery(SystemInstall $systemInstall, ProcessDto $dto): string
    {
        $lastSync = $this->lastSyncManager->getLastSync($systemInstall, $dto->getHeaders());
        $times    = CronUtils::getTimes($lastSync);

        return sprintf('AND MetaData.LastUpdatedTime >= \'%s\' AND MetaData.LastUpdatedTime < \'%s\'',
            $times->getStart()->format(DateTime::ATOM), $times->getEnd()->format(DateTime::ATOM));
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

}