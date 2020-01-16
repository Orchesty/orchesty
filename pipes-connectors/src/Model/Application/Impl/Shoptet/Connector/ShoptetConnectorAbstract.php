<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Utils\ProcessHeaderTrait;
use Hanaboso\Utils\Traits\UrlBuilderTrait;

/**
 * Class ShoptetConnectorAbstract
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector
 */
abstract class ShoptetConnectorAbstract extends ConnectorAbstract
{

    protected const ID                = 'id';
    protected const TYPE              = 'type';
    protected const DATA              = 'data';
    protected const REPEATER_INTERVAL = 5_000;

    use UrlBuilderTrait;
    use ProcessHeaderTrait;

    /**
     * @var DocumentRepository<ApplicationInstall>&ApplicationInstallRepository
     */
    protected $repository;

    /**
     * @var CurlManager
     */
    protected $sender;

    /**
     * ShoptetConnectorAbstract constructor.
     *
     * @param DocumentManager $dm
     * @param CurlManager     $sender
     */
    public function __construct(DocumentManager $dm, CurlManager $sender)
    {
        $this->repository = $dm->getRepository(ApplicationInstall::class);
        $this->sender     = $sender;
        $this->host       = ShoptetApplication::SHOPTET_URL;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ApplicationInstall
     * @throws ConnectorException
     */
    protected function getApplicationInstall(ProcessDto $dto): ApplicationInstall
    {
        $id = $this->getHeaderByKey($dto, self::ID);

        /** @var ApplicationInstall|null $applicationInstall */
        $applicationInstall = $this->repository->findOneBy([self::ID => $id]);

        if (!$applicationInstall) {
            throw $this->createMissingApplicationInstallException($id);
        }

        return $applicationInstall;
    }

    /**
     * @param mixed[]    $data
     * @param ProcessDto $dto
     *
     * @return mixed[]
     * @throws ConnectorException
     * @throws OnRepeatException
     */
    protected function processResponse(array $data, ProcessDto $dto): array
    {
        $isRepeatable = FALSE;

        if (isset($data['errors'])) {
            $exception = $this->createException(
                implode(
                    PHP_EOL,
                    array_map(
                        static function (array $message) use (&$isRepeatable): string {
                            if ($message['instance'] === 'url-locked') {
                                $isRepeatable = TRUE;
                            }

                            return sprintf('%s: %s', $message['errorCode'], $message['message']);
                        },
                        $data['errors']
                    )
                )
            );

            if ($isRepeatable) {
                throw $this->createRepeatException($dto, $exception, self::REPEATER_INTERVAL, self::REPEATER_INTERVAL);
            }

            throw $exception;
        }

        return $data;
    }

}
