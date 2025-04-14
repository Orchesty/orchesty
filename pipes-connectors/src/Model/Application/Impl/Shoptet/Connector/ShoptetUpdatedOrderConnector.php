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
 * Class ShoptetUpdatedOrderConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetUpdatedOrderConnector extends ShoptetConnectorAbstract
{

    public const string NAME = 'shoptet-updated-order-connector';

    private const string URL = 'api/orders/%s?include=notes';

    private const string EVENT_INSTANCE = 'eventInstance';
    private const string ORDER          = 'order';

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
        try {
            $data = $this->processResponse(
                $this->getSender()->send(
                    $this->getApplication()->getRequestDto(
                        $dto,
                        $this->getApplicationInstallFromProcess($dto),
                        CurlManager::METHOD_GET,
                        $this->getUrl(self::URL, $dto->getJsonData()[self::EVENT_INSTANCE] ?? ''),
                    ),
                )->getJsonBody(),
                $dto,
            )[self::DATA][self::ORDER];

            return $dto->setJsonData($data);
        } catch (CurlException $e) {
            throw new OnRepeatException(
                $dto,
                sprintf("Connector '%s': %s: %s", $this->getName(), $e::class, $e->getMessage()),
                $e->getCode(),
            );
        }
    }

}
