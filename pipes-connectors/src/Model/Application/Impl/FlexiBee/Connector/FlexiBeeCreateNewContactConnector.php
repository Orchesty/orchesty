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
 * Class FlexiBeeCreateNewContactConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\Connector
 */
final class FlexiBeeCreateNewContactConnector extends ConnectorAbstract
{

    private const NAME = 'flexibee.create-new-contact';

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
            $acceptedParams = ['name', 'use-demo', 'country', 'org-type', 'ic', 'vatid'];

            $countryArrayWithTypes = [
                'CZ' => [
                    'PODNIKATELE+PU',
                    'PODNIKATELE+DE',
                    'NEZISKOVE',
                    'ROZPOCTOVE',
                ],
                'SK' => [
                    'PODNIKATELIA+PU',
                ],
            ];

            $url          = '';
            $dtoDataArray = $dto->getJsonData();

            foreach ($dtoDataArray as $key => $value) {
                if (in_array($key, $acceptedParams, TRUE) && isset($value)) {

                    switch ($key) {
                        case 'name':
                            $name = $value;

                            break;
                        case 'country':
                            if (array_key_exists($value, $countryArrayWithTypes))
                                $country = $value;

                            break;
                        case 'org-type':
                            $orgType = $value;

                            break;
                    }

                    $url = sprintf('%s%s=%s&', $url, $key, $value);
                }
            }

            if (!isset($name)) {
                return $dto->setStopProcess(ProcessDto::DO_NOT_CONTINUE, 'Název organizace musí být vyplněný');
            }

            if (isset($country)) {
                if (isset($orgType)) {
                    if (!in_array($orgType, $countryArrayWithTypes[$country], TRUE)) {
                        return $dto->setStopProcess(
                            ProcessDto::DO_NOT_CONTINUE,
                            'Zvolený typ organizace není platný.',
                        );
                    }
                }
            } else {
                if (isset($orgType)) {
                    return $dto->setStopProcess(ProcessDto::DO_NOT_CONTINUE, 'Zvolený typ organizace není platný.');
                }
            }

            $url = rtrim($url, '&');

            $applicationInstall = $this->getApplicationInstallFromProcess($dto);

            /** @var FlexiBeeApplication $application */
            $application = $this->getApplication();
            $request     = $application
                ->getRequestDto(
                    $dto,
                    $applicationInstall,
                    CurlManager::METHOD_PUT,
                    (string) $application->getUrl($applicationInstall, sprintf('%s%s', 'admin/zalozeni-firmy?', $url)),
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
