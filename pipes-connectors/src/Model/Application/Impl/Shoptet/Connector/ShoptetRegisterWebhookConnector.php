<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\String\Json;
use JsonException;

/**
 * Class ShoptetRegisterWebhookConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector
 */
final class ShoptetRegisterWebhookConnector extends ShoptetConnectorAbstract
{

    use ProcessEventNotSupportedTrait;

    private const WEBHOOK_URL = 'api/webhooks';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shoptet-register-webhook-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws MongoDBException
     * @throws OnRepeatException
     * @throws ConnectorException
     * @throws JsonException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        /** @var ShoptetApplication $application */
        $application        = $this->application;
        $applicationInstall = $this->repository->findUserAppByHeaders($dto);

        $requestDto = $application
            ->getRequestDto($applicationInstall, CurlManager::METHOD_POST, $this->getUrl(self::WEBHOOK_URL))
            ->setDebugInfo($dto);
        foreach ($application->getWebhookSubscriptions() as $subscription) {
            try {
                $this->processResponse(
                    $this->sender->send(
                        $requestDto->setBody(
                            Json::encode(
                                [
                                    'data' => [
                                        [
                                            'event' => $subscription->getParameters()['event'],
                                            'url'   => $application->getTopologyUrl(
                                                $subscription->getTopology(),
                                                $subscription->getNode()
                                            ),
                                        ],
                                    ],
                                ]
                            )
                        )
                    )->getJsonBody(),
                    $dto
                );
            } catch (CurlException $exception) {
                throw $this->createRepeatException($dto, $exception);
            }
        }

        return $dto;
    }

}
