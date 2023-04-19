<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\Connector;

use GuzzleHttp\RequestOptions;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\GoogleDriveApplication;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;

/**
 * Class GoogleDriveUploadFileConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\GoogleDrive\Connector
 */
final class GoogleDriveUploadFileConnector extends ConnectorAbstract
{

    public const NAME = 'google-drive.upload-file';

    /**
     * @var string
     */
    protected string $fileName = 'my.txt';

    /**
     * @var string
     */
    protected string $folder = 'id';

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws OnRepeatException
     * @throws ApplicationInstallException
     * @throws PipesFrameworkException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->getApplicationInstallFromProcess($dto);
        $tmpFileName        = sprintf('/tmp/%s', uniqid('file_', FALSE));
        File::putContent($tmpFileName, $dto->getData());

        $multipart = [
            RequestOptions::MULTIPART => [
                [
                    'contents' => Json::encode(['name' => $this->fileName, 'parents' => [$this->folder]]),
                    'headers'  => ['Content-Type' => 'application/json; charset=UTF-8'],
                    'name'     => 'metadata',
                ],
                [
                    'contents' => fopen($tmpFileName, 'r'),
                    'headers'  => ['Content-Type' => 'application/octet-stream'],
                    'name'     => 'file',
                ],
            ],
        ];

        try {
            $request = $this->getApplication()
                ->getRequestDto(
                    $dto,
                    $applicationInstall,
                    CurlManager::METHOD_POST,
                    sprintf('%s/upload/drive/v3/files?uploadType=multipart', GoogleDriveApplication::BASE_URL),
                );

            $response = $this->getSender()->send($request, $multipart);

            $this->evaluateStatusCode($response->getStatusCode(), $dto);

            $dto->setData($response->getBody());
        } catch (CurlException | ConnectorException $e) {
            throw new OnRepeatException($dto, $e->getMessage(), $e->getCode(), $e);
        } finally {
            unlink($tmpFileName);
        }

        return $dto;
    }

}
