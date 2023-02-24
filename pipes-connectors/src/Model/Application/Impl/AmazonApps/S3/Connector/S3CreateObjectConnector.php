<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\Connector;

use Aws\Exception\AwsException;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
use Hanaboso\Utils\File\File;

/**
 * Class S3CreateObjectConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\Connector
 */
final class S3CreateObjectConnector extends S3ObjectConnectorAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws ConnectorException
     * @throws CustomNodeException
     * @throws GuzzleException
     * @throws OnRepeatException
     * @throws Exception
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $content = $dto->getJsonData();
        $this->checkParameters([self::NAME, self::CONTENT], $content);

        $applicationInstall = $this->getApplicationInstallFromProcess($dto);

        /** @var S3Application $application */
        $application = $this->getApplication();
        $client      = $application->getS3Client($applicationInstall);

        $path = sprintf('/tmp/%s', bin2hex(random_bytes(10)));
        File::putContent($path, $content[self::CONTENT]);

        try {
            $client->putObject(
                [
                    self::BUCKET => $this->getBucket($applicationInstall),
                    self::KEY    => $content[self::NAME],
                    self::SOURCE => $path,
                ],
            );
        } catch (AwsException $e) {
            throw new OnRepeatException(
                $dto,
                sprintf("Connector '%s': %s: %s", $this->getName(), $e::class, $e->getMessage()),
            );
        } finally {
            unlink($path);
        }

        return $dto->setJsonData([self::NAME => $content[self::NAME]]);
    }

    /**
     * @return string
     */
    protected function getCustomName(): string
    {
        return 'create-object';
    }

}
