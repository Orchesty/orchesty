<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\Connector;

use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\IDokladApplication;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\Validations\Validations;
use LogicException;

/**
 * Class IDokladCreateNewContactConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\Connector
 */
final class IDokladCreateNewContactConnector extends ConnectorAbstract
{

    public const string NAME = 'i-doklad.create-new-contact';

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
        try {
            $data = $dto->getJsonData();
            Validations::checkParams(
                [
                    'CompanyName',
                    'CountryId',
                    'Name',
                ],
                $data,
            );

            $applicationInstall = $this->getApplicationInstallFromProcess($dto);

            $request = $this->getApplication()
                ->getRequestDto(
                    $dto,
                    $applicationInstall,
                    CurlManager::METHOD_POST,
                    sprintf('%s/Contacts', IDokladApplication::BASE_URL),
                )->setBody($dto->getData());

            $response = $this->getSender()->send($request);

            $this->evaluateStatusCode($response->getStatusCode(), $dto);

            $dto->setData($response->getBody());
        } catch (CurlException | ConnectorException $e) {
            throw new OnRepeatException($dto, $e->getMessage(), $e->getCode(), $e);
        } catch (LogicException $e) {
            return $dto->setStopProcess(ProcessDtoAbstract::DO_NOT_CONTINUE, $e->getMessage());
        }

        return $dto;
    }

}
