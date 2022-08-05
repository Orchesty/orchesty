<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector;

use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Class ShoptetGetEshopInfo
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetGetEshopInfo extends ShoptetConnectorAbstract
{

    public const NAME = 'shoptet-get-eshop-info';

    private const GET_ESHOP_INFO = '/api/eshop?include=orderAdditionalFields%2CorderStatuses%2CshippingMethods%2CpaymentMethods';

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
     * @throws ApplicationInstallException
     * @throws ConnectorException
     * @throws OnRepeatException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->getApplicationInstallFromProcess($dto);
        try {
            $response = $this->processActionArray($applicationInstall, $dto);
        } catch (CurlException $e) {
            throw new OnRepeatException(
                $dto,
                sprintf("Connector '%s': %s: %s", $this->getName(), $e::class, $e->getMessage()),
                $e->getCode(),
            );
        }

        return $dto->setJsonData($response);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param ProcessDto         $processDto
     *
     * @return mixed[]
     * @throws ConnectorException
     * @throws CurlException
     */
    public function processActionArray(ApplicationInstall $applicationInstall, ProcessDto $processDto): array
    {
        $requestDto = $this->getApplication()->getRequestDto(
            $processDto,
            $applicationInstall,
            CurlManager::METHOD_GET,
            sprintf('%s%s', $this->host, self::GET_ESHOP_INFO),
        );

        return $this->getSender()->send($requestDto)->getJsonBody();
    }

}
