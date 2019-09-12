<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\S3\Connector;

use Aws\Exception\AwsException;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Class UpdateObjectConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\S3\Connector
 */
final class DeleteObjectConnector extends ObjectConnectorAbstract
{

    /**
     * @return string
     */
    protected function getCustomId(): string
    {
        return 'delete-object';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     * @throws OnRepeatException
     * @throws ApplicationInstallException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $content = $this->getContent($dto);
        $this->checkParameters([self::NAME], $content);

        $applicationInstall = $this->getApplicationInstall($dto);
        $client             = $this->getApplication()->getS3Client($applicationInstall);

        try {
            $client->deleteObject([
                self::BUCKET => $this->getBucket($applicationInstall),
                self::KEY    => $content[self::NAME],
            ]);
        } catch (AwsException $e) {
            throw $this->createRepeatException($dto, $e);
        }

        return $this->setContent($dto, [self::NAME => $content[self::NAME]]);
    }

}
