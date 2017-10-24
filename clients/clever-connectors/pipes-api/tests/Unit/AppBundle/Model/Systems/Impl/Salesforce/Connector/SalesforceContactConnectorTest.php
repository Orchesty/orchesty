<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Connector;

use DateTime;
use Tests\KernelTestCaseAbstract;

/**
 * Class SalesforceContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Connector
 */
final class SalesforceContactConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testGetTimeQuery(): void
    {
        $conn = $this->container->get('hbpf.connector.salesforce-update-contact-connector');

        $from = new DateTime('-3 days');
        $to = new DateTime();

        $res = $conn->getTimeQuery($from, $to);
        self::assertEquals(sprintf(' where LastModifiedDate>%s and LastModifiedDate<=%s', $from->format(DateTime::ISO8601), $to->format(DateTime::ISO8601)), urldecode($res));

        $res = $conn->getTimeQuery(NULL, $to);
        self::assertEquals(sprintf(' where LastModifiedDate<=%s', $to->format(DateTime::ISO8601)), urldecode($res));
    }

}