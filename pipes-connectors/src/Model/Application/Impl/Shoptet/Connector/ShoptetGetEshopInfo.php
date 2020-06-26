<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector;

use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;

/**
 * Class ShoptetGetEshopInfo
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector
 */
class ShoptetGetEshopInfo extends ShoptetConnectorAbstract
{

    use ProcessEventNotSupportedTrait;

    private const GET_ESHOP_INFO = '/api/eshop?include=orderAdditionalFields%2CorderStatuses%2CshippingMethods%2CpaymentMethods';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shoptet-get-eshop-info';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws OnRepeatException
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->repository->findUserAppByHeaders($dto);
        try {
            $response = $this->processActionArray($applicationInstall, $dto);
        } catch (CurlException $exception) {
            throw $this->createRepeatException($dto, $exception);
        }

        return $this->setJsonContent($dto, $response);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param ProcessDto|null    $processDto
     *
     * @return mixed[]
     * @throws CurlException
     * @throws ConnectorException
     */
    public function processActionArray(ApplicationInstall $applicationInstall, ?ProcessDto $processDto = NULL): array
    {
        $requestDto = $this->getApplication()->getRequestDto(
            $applicationInstall,
            CurlManager::METHOD_GET,
            sprintf('%s%s', $this->host, self::GET_ESHOP_INFO)
        );
        if ($processDto) {
            $requestDto->setDebugInfo($processDto);
        }

        return $this->sender->send($requestDto)->getJsonBody();
    }

}
