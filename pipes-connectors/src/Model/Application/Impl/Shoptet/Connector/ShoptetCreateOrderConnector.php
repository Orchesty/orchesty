<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector;

use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFAppStore\Document\Synchronization;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessExceptionTrait;
use JsonException;

/**
 * Class ShoptetCreateOrderConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetCreateOrderConnector extends ShoptetConnectorAbstract
{

    use ProcessExceptionTrait;
    use ProcessEventNotSupportedTrait;

    private const URL   = '/api/orders';
    private const CODE  = 'code';
    private const ORDER = 'order';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shoptet-create-order';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     * @throws OnRepeatException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->getApplicationInstall($dto);

        try {
            $response = $this->processResponse(
                $this->sender->send(
                    $this->application->getRequestDto(
                        $applicationInstall,
                        CurlManager::METHOD_POST,
                        sprintf('%s%s', $this->host, self::URL),
                        $dto->getData()
                    )->setDebugInfo($dto)
                )->getJsonBody(),
                $dto
            );

            $externalId = $response[self::DATA][self::ORDER][self::CODE];
            $this->setHeader($dto, Synchronization::EXTERNAL_ID_HEADER, $externalId);

            return $this->setJsonContent($dto, $response);
        } catch (ApplicationInstallException | CurlException | JsonException $e) {
            throw $this->createRepeatException($dto, $e, self::REPEATER_INTERVAL);
        }
    }

}
