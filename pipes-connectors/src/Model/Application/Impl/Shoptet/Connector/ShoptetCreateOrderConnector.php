<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector;

use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;

/**
 * Class ShoptetCreateOrderConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetCreateOrderConnector extends ShoptetConnectorAbstract
{

    public const NAME = 'shoptet-create-order';

    private const URL = '/api/orders';

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
     * @throws GuzzleException
     * @throws CustomNodeException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->getApplicationInstallFromProcess($dto);

        try {
            $response = $this->processResponse(
                $this->getSender()->send(
                    $this->getApplication()->getRequestDto(
                        $dto,
                        $applicationInstall,
                        CurlManager::METHOD_POST,
                        sprintf('%s%s', $this->host, self::URL),
                        $dto->getData(),
                    ),
                )->getJsonBody(),
                $dto,
            );

            return $dto->setJsonData($response);
        } catch (CurlException $e) {
            throw new OnRepeatException(
                $dto,
                sprintf("Connector '%s': %s: %s", $this->getName(), $e::class, $e->getMessage()),
                $e->getCode(),
            );
        }
    }

}
