<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\Connector;

use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\AwsObjectConnectorAbstract;

/**
 * Class RedshiftObjectConnectorAbstract
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\Connector
 */
abstract class RedshiftObjectConnectorAbstract extends AwsObjectConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return sprintf('redshift-%s', $this->getCustomId());
    }

}