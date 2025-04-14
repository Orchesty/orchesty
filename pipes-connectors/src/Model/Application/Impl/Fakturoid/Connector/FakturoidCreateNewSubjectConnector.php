<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector;

use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;

/**
 * Class FakturoidCreateNewSubjectConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector
 */
final class FakturoidCreateNewSubjectConnector extends FakturoidAbstractConnector
{

    protected const string NAME     = 'fakturoid.create-new-subject';
    protected const string ENDPOINT = 'subjects.json';
    protected const string METHOD   = CurlManager::METHOD_POST;

}
