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
     * @param Times $times
     *
     * @return string
     */
    protected function getTimeQuery(Times $times): string
    {
        $since = '';
        if ($times->getStart()) {
            $time  = $times->getStart()->format(DateTime::ATOM);
            $since = sprintf(' AND MetaData.LastUpdatedTime >= \'%s\' AND MetaData.CreateTime < \'%s\'',
                $time, $time);
        }

        $till = sprintf(' AND MetaData.LastUpdatedTime < \'%s\'', $times->getEnd()->format(DateTime::ATOM));

        return $since . $till;
    }

}