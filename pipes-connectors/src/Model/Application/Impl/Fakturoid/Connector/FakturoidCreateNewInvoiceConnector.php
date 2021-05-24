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

    protected const NAME     = 'fakturoid.create-new-invoice';
    protected const ENDPOINT = 'invoices.json';
    protected const METHOD   = CurlManager::METHOD_POST;

}
