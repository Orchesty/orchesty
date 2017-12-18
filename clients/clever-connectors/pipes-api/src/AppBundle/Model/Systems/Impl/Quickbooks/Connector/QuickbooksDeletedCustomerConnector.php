<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/23/17
 * Time: 11:19 AM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector;

use CleverConnectors\AppBundle\Utils\Dto\Times;
use DateTime;

/**
 * Class QuickbooksDeletedCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector
 */
class QuickbooksDeletedCustomerConnector extends QuickbooksCustomerConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'quickbooks-deleted-customer-connector';
    }

    /**
     * @param Times $times
     *
     * @return string
     */
    protected function getTimeQuery(Times $times): string
    {
        $since = $times->getStart() ? sprintf(' AND MetaData.LastUpdatedTime >= \'%s\'',
            $times->getStart()->format(DateTime::ATOM)) : '';
        $till  = sprintf(' AND MetaData.LastUpdatedTime < \'% s\'', $times->getEnd()->format(DateTime::ATOM));

        return $since . $till;
    }

    /**
     * @param Times|null $times
     *
     * @return string
     */
    protected function getTotalQuery(?Times $times = NULL): string
    {
        return 'SELECT COUNT(*) FROM customer WHERE Active = false' . $this->getTimeQuery($times);
    }

    /**
     * @param int        $start
     * @param int        $count
     * @param Times|null $times
     *
     * @return string
     */
    protected function getDataQuery(int $start, int $count, ?Times $times = NULL): string
    {
        return sprintf('SELECT * FROM customer WHERE Active = false' . $this->getTimeQuery($times)
            . ' STARTPOSITION %d MAXRESULTS %d', $start, $count);
    }

}