<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessExceptionTrait;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use JsonException;

/**
 * Class ShoptetUpdateOrderConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetUpdateOrderConnector extends ShoptetConnectorAbstract
{

    use ProcessEventNotSupportedTrait;
    use ProcessExceptionTrait;

    private const URL    = '/api/orders/%s/status?suppressDocumentGeneration=true&suppressEmailSending=true&suppressSmsSending=true';
    private const STATUS = 'statusId';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shoptet-update-order';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     * @throws OnRepeatException
     * @throws PipesFrameworkException
     * @throws DocumentNotFoundException
     * @throws MongoDBException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->getApplicationInstall($dto);

        try {
            $response = $this->processResponse(
                $this->sender->send(
                    $this->application->getRequestDto(
                        $applicationInstall,
                        CurlManager::METHOD_PATCH,
                        sprintf(
                            '%s%s',
                            $this->host,
                            sprintf(
                                self::URL,
                                $applicationInstall->getSettings(
                                )[ApplicationAbstract::FORM][ShoptetApplication::ESHOP_ID]
                            )
                        ),
                        Json::encode([self::DATA => [self::STATUS => $this->getStatus($dto)]])
                    )->setDebugInfo($dto)
                )->getJsonBody(),
                $dto
            );

            return $this->setJsonContent($dto, $response)->setStopProcess();
        } catch (ApplicationInstallException | CurlException | JsonException $e) {
            throw $this->createRepeatException($dto, $e, self::REPEATER_INTERVAL);
        }
    }

    /**
     * @param ProcessDto $dto
     *
     * @return int
     * @throws ConnectorException
     */
    private function getStatus(ProcessDto $dto): int
    {
        $settings = $this->getApplicationInstall($dto)->getSettings()[ShoptetApplication::FORM];

        if (!isset($settings[ShoptetApplication::CANCELLED])) {
            throw $this->createException("Unsupported order status '%s'!", ShoptetApplication::CANCELLED);
        }

        return (int) $settings[ShoptetApplication::CANCELLED];
    }

}
