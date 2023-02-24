<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector;

use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\String\Json;

/**
 * Class ShoptetRegisterWebhookConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetRegisterWebhookConnector extends ShoptetConnectorAbstract
{

    public const NAME = 'shoptet-register-webhook-connector';

    private const WEBHOOK_URL = 'api/webhooks';

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
     * @throws CurlException
     * @throws CustomNodeException
     * @throws DateTimeException
     * @throws GuzzleException
     * @throws OnRepeatException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        /** @var ShoptetApplication $application */
        $application        = $this->application;
        $applicationInstall = $this->getApplicationInstallFromProcess($dto);

        $requestDto = $application
            ->getRequestDto($dto, $applicationInstall, CurlManager::METHOD_POST, $this->getUrl(self::WEBHOOK_URL));
        foreach ($application->getWebhookSubscriptions() as $subscription) {
            try {
                $this->processResponse(
                    $this->getSender()->send(
                        $requestDto->setBody(
                            Json::encode(
                                [
                                    'data' => [
                                        [
                                            'event' => $subscription->getParameters()['event'],
                                            'url'   => $application->getTopologyUrl(
                                                $subscription->getTopology(),
                                                $subscription->getNode(),
                                            ),
                                        ],
                                    ],
                                ],
                            ),
                        ),
                    )->getJsonBody(),
                    $dto,
                );
            } catch (CurlException $e) {
                throw new OnRepeatException(
                    $dto,
                    sprintf("Connector '%s': %s: %s", $this->getName(), $e::class, $e->getMessage()),
                    $e->getCode(),
                );
            }
        }

        return $dto;
    }

}
