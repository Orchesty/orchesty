<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\Connector;

use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\FlexiBeeApplication;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\Exception\PipesFrameworkException;

/**
 * Class FlexiBeeGetContactsArrayConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\Connector
 */
final class FlexiBeeGetContactsArrayConnector extends ConnectorAbstract
{

    private const NAME = 'flexibee.get-contacts-array';

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
     * @throws DateTimeException
     * @throws OnRepeatException
     * @throws PipesFrameworkException
     * @throws GuzzleException
     * @throws CustomNodeException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        try {
            $applicationInstall = $this->getApplicationInstallFromProcess($dto);

            /** @var FlexiBeeApplication $application */
            $application = $this->getApplication();
            $request     = $application
                ->getRequestDto(
                    $dto,
                    $applicationInstall,
                    CurlManager::METHOD_GET,
                    (string) $application->getUrl($applicationInstall, 'kontakt.json'),
                );

            $response = $this->getSender()->send($request);

            $this->evaluateStatusCode($response->getStatusCode(), $dto);

            $dto->setData($response->getBody());
        } catch (CurlException | ConnectorException $e) {
            throw new OnRepeatException($dto, $e->getMessage(), $e->getCode(), $e);
        }

        return $dto;
    }

}
