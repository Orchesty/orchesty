<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector;

use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;

/**
 * Class FakturoidCreateNewInvoiceConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector
 */
final class FakturoidCreateNewInvoiceConnector extends FakturoidAbstractConnector
{

    protected const string NAME     = 'fakturoid.create-new-invoice';
    protected const string ENDPOINT = 'invoices.json';
    protected const string METHOD   = CurlManager::METHOD_POST;

}
