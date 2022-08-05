<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\Connector;

use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\AwsObjectConnectorAbstract;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;

/**
 * Class S3ObjectConnectorAbstract
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\Connector
 */
abstract class S3ObjectConnectorAbstract extends AwsObjectConnectorAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return sprintf('s3-%s', $this->getCustomName());
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    protected function getBucket(ApplicationInstall $applicationInstall): string
    {
        return $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][S3Application::BUCKET];
    }

}
