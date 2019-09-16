<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\S3\Connector;

use Aws\Exception\AwsException;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Class CreateObjectConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\S3\Connector
 */
final class CreateObjectConnector extends ObjectConnectorAbstract
{

    /**
     * @return string
     */
    protected function getCustomId(): string
    {
        return 'create-object';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     * @throws ApplicationInstallException
     * @throws Exception
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $content = $this->getContent($dto);
        $this->checkParameters([self::NAME, self::CONTENT], $content);

        $applicationInstall = $this->getApplicationInstall($dto);
        $client             = $this->getApplication()->getS3Client($applicationInstall);

        $path = sprintf('/tmp/%s', bin2hex(random_bytes(10)));
        file_put_contents($path, $content[self::CONTENT]);

        try {
            $client->putObject([
                self::BUCKET => $this->getBucket($applicationInstall),
                self::KEY    => $content[self::NAME],
                self::SOURCE => $path,
            ]);
        } catch (AwsException $e) {
            throw $this->createRepeatException($dto, $e);
        } finally {
            unlink($path);
        }

        return $this->setContent($dto, [self::NAME => $content[self::NAME]]);
    }

}
