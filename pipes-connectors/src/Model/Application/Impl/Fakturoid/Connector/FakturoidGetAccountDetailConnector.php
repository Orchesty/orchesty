<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector;

use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;

/**
 * Class FakturoidGetAccountDetailConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector
 */
final class FakturoidGetAccountDetailConnector extends FakturoidAbstractConnector
{

    protected const string NAME     = 'fakturoid.get-account-detail';
    protected const string ENDPOINT = 'account.json';
    protected const string METHOD   = CurlManager::METHOD_GET;

}
