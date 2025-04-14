<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector;

use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
use Hanaboso\Utils\Exception\PipesFrameworkException;

/**
 * Class ShoptetUpdateOrderConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetUpdateOrderConnector extends ShoptetConnectorAbstract
{

    public const string NAME = 'shoptet-update-order';

    private const string URL = '/api/orders/%s/status?suppressDocumentGeneration=true&suppressEmailSending=true&suppressSmsSending=true';

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
     * @throws PipesFrameworkException
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
                        CurlManager::METHOD_PATCH,
                        sprintf(
                            '%s%s',
                            $this->host,
                            sprintf(
                                self::URL,
                                $applicationInstall->getSettings(
                                )[ApplicationInterface::AUTHORIZATION_FORM][ShoptetApplication::ESHOP_ID],
                            ),
                        ),
                    ),
                )->getJsonBody(),
                $dto,
            );

            return $dto->setJsonData($response)->setStopProcess(ProcessDtoAbstract::DO_NOT_CONTINUE, 'Order updated');
        } catch (CurlException $e) {
            throw new OnRepeatException(
                $dto,
                sprintf("Connector '%s': %s: %s", $this->getName(), $e::class, $e->getMessage()),
                $e->getCode(),
            );
        }
    }

}
