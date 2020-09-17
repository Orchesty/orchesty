<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\Connector;

use Aws\Exception\AwsException;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use JsonException;

/**
 * Class S3DeleteObjectConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\Connector
 */
final class S3DeleteObjectConnector extends S3ObjectConnectorAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     * @throws OnRepeatException
     * @throws ApplicationInstallException
     * @throws JsonException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $content = $this->getJsonContent($dto);
        $this->checkParameters([self::NAME], $content);

        $applicationInstall = $this->getApplicationInstall($dto);
        /** @var S3Application $application */
        $application = $this->getApplication();
        $client      = $application->getS3Client($applicationInstall);

        try {
            $client->deleteObject(
                [
                    self::BUCKET => $this->getBucket($applicationInstall),
                    self::KEY    => $content[self::NAME],
                ]
            );
        } catch (AwsException $e) {
            throw $this->createRepeatException($dto, $e);
        }

        return $this->setJsonContent($dto, [self::NAME => $content[self::NAME]]);
    }

    /**
     * @return string
     */
    protected function getCustomId(): string
    {
        return 'delete-object';
    }

}
