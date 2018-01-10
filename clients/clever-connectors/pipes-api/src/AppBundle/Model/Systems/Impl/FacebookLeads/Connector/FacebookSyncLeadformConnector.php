<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 12/6/17
 * Time: 10:37 AM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\NotificationTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\FacebookLeadsSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\LoggerUtils;
use Clue\React\Buzz\Message\ResponseException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class FacebookSyncLeadformConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Connector
 */
class FacebookSyncLeadformConnector implements BatchInterface, ConnectorInterface, LoggerAwareInterface
{

    use LoggerAwareTrait;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;
    /**
     *
     * @var FacebookLeadsSystem
     */
    private $system;

    /**
     * @var CurlSenderFactory
     */
    private $factory;

    /**
     * FacebookSyncLeadformConnector constructor.
     *
     * @param FacebookLeadsSystem $system
     * @param CurlSenderFactory   $factory
     * @param DocumentManager     $dm
     */
    public function __construct(
        FacebookLeadsSystem $system,
        CurlSenderFactory $factory,
        DocumentManager $dm
    )
    {
        $this->system                  = $system;
        $this->factory                 = $factory;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->logger                  = new NullLogger();
    }

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $sender        = $this->factory->create($loop);
        $systemInstall = $this->getSystemInstall($dto);
        $requestDto    = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $settings = $systemInstall->getSettings();
        $url      = new Uri(sprintf(
            '%s/%s/leads?fields=%s&access_token=%s',
            $requestDto->getUri(TRUE),
            $settings['form_id'],
            urlencode('created_time,id,ad_id,form_id,field_data'),
            urlencode($settings[OAuth2Provider::ACCESS_TOKEN])
        ));
        $promise  = $sender->send(RequestDto::from($requestDto, $url))
            ->then(
                function (ResponseInterface $response): SuccessMessage {
                    return $this->createSuccessMessage($response);
                },
                function (ResponseException $exception) use ($systemInstall): void {
                    if ($exception->getCode() == 400) {
                        $body = $exception->getResponse()->getBody()->getContents();
                        $data = json_decode($body, TRUE);
                        if (isset($data['error']['code']) && $data['error']['code'] == 190) {
                            $this->logger->info(
                                NotificationTypeEnum::ACCESS_EXPIRATION,
                                LoggerUtils::getMessage($this->system, $systemInstall)
                            );
                        }
                    }
                    if ($exception->getCode() == 500) {
                        $this->logger->info(
                            NotificationTypeEnum::SERVICE_UNAVAILABLE,
                            LoggerUtils::getMessage($this->system, $systemInstall)
                        );
                    }
                    throw $exception;
                }
            )
            ->then($callbackItem);

        $this->systemInstallRepository->setSyncTime($systemInstall);

        return $promise;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebook-sync-leadform-conector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Facebook Leads has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Facebook Leads  has not implemented "processAction" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return SystemInstall
     */
    protected function getSystemInstall(ProcessDto $dto): SystemInstall
    {
        return $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
    }

    /**
     * @param ResponseInterface $response
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    private function createSuccessMessage(ResponseInterface $response): SuccessMessage
    {
        $data = json_decode($response->getBody()->getContents(), TRUE);
        if (is_array($data) && array_key_exists('data', $data)) {
            $successMessage = new SuccessMessage(0);
            $successMessage->setData(json_encode($data['data']));
            unset($data);

            return $successMessage;
        }
        throw new SystemException(
            'Facebook Leads Error: Key [data -> field_data] not found in response.',
            SystemException::MISSING_RESPONSE_DATA
        );
    }

}