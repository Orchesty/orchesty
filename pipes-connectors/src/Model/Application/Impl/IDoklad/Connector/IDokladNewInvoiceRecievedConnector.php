<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\Connector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\IDokladApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Validations\Validations;
use LogicException;

/**
 * Class IDokladNewInvoiceRecievedConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\IDoklad\Connector
 */
final class IDokladNewInvoiceRecievedConnector extends ConnectorAbstract
{

    use ProcessEventNotSupportedTrait;

    /**
     * @var ApplicationInstallRepository
     */
    private ApplicationInstallRepository $repository;

    /**
     * IDokladNewInvoiceRecievedConnector constructor.
     *
     * @param DocumentManager $dm
     * @param CurlManager     $sender
     */
    public function __construct(DocumentManager $dm, private CurlManager $sender)
    {
        $this->repository = $dm->getRepository(ApplicationInstall::class);
        $this->sender->setTimeout(10);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'i-doklad.new-invoice-recieved';
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
            $data = Json::decode($dto->getData());
            Validations::checkParams(
                [
                    'DateOfMaturity',
                    'DateOfReceiving',
                    'Description',
                    'DocumentSerialNumber',
                    'IsIncomeTax',
                    'Items' => [
                        [
                            'Name',
                            'PriceType',
                            'UnitPrice',
                            'VatRateType',
                        ],
                    ],
                    'PartnerId',
                    'PaymentOptionId',
                ],
                $data,
            );

            $applicationInstall = $this->repository->findUserAppByHeaders($dto);

            $request = $this->getApplication()
                ->getRequestDto(
                    $applicationInstall,
                    CurlManager::METHOD_POST,
                    sprintf('%s/ReceivedInvoices', IDokladApplication::BASE_URL),
                )->setBody($dto->getData())
                ->setDebugInfo($dto);

            $response = $this->sender->send($request);

            $this->evaluateStatusCode($response->getStatusCode(), $dto);

            $dto->setData($response->getBody());
        } catch (CurlException | ConnectorException $e) {
            throw new OnRepeatException($dto, $e->getMessage(), $e->getCode(), $e);
        } catch (LogicException) {
            return $dto->setStopProcess();
        }

        return $dto;
    }

}
