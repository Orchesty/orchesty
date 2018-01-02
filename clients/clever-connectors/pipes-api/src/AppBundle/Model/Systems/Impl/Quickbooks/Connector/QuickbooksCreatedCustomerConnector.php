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
 * Class QuickbooksCreatedCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector
 */
class QuickbooksCreatedCustomerConnector extends QuickbooksCustomerConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'quickbooks-created-customer-connector';
    }

    /**
     * @param Times $times
     *
     * @return string
     */
    protected function getTimeQuery(Times $times): string
    {
        $since = $times->getStart() ? sprintf(' AND MetaData.CreateTime >= \'%s\'',
            $times->getStart()->format(DateTime::ATOM)) : '';
        $till  = sprintf(' AND MetaData.CreateTime < \'%s\'', $times->getEnd()->format(DateTime::ATOM));

        return $since . $till;
    }

}