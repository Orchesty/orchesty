<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector;

use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\HubSpotApplication;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\System\PipesHeaders;
use Hanaboso\Utils\Traits\LoggerTrait;
use Psr\Log\LoggerAwareInterface;

/**
 * Class HubSpotCreateContactConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector
 */
final class HubSpotCreateContactConnector extends ConnectorAbstract implements LoggerAwareInterface
{

    use LoggerTrait;

    public const NAME = 'hub-spot.create-contact';

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
     * @throws OnRepeatException
     * @throws PipesFrameworkException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->getApplicationInstallFromProcess($dto);
        $body               = $dto->getJsonData();

        try {
            $response = $this->getSender()->send(
                $this->getApplication()->getRequestDto(
                    $dto,
                    $applicationInstall,
                    CurlManager::METHOD_POST,
                    sprintf('%s/contacts/v1/contact/', HubSpotApplication::BASE_URL),
                    Json::encode($body),
                ),
            );
            $message  = $response->getJsonBody()['validationResults'][0]['message'] ?? NULL;
            $this->evaluateStatusCode($response->getStatusCode(), $dto, $message);

            if ($response->getStatusCode() === 409) {
                $parsed = $response->getJsonBody();
                $this->logger->error(
                    sprintf('Contact "%s" already exist.', $parsed['identityProfile']['identity'][0]['value'] ?? ''),
                    array_merge(
                        ['response' => $response->getBody(), PipesHeaders::debugInfo($dto->getHeaders())],
                    ),
                );
            }

            $dto->setData($response->getBody());
        } catch (CurlException | ConnectorException $e) {
            throw new OnRepeatException($dto, $e->getMessage(), $e->getCode(), $e);
        }

        return $dto;
    }

}
