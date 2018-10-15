<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CMEvents;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Requester\ResultDto;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Clue\React\Buzz\Message\ResponseException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSender;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use RuntimeException;
use function React\Promise\all;

/**
 * Class CMEventActivator
 *
 * @package CleverConnectors\AppBundle\Model\CMEvents
 */
class CMEventActivator implements BatchInterface, CustomNodeInterface, LoggerAwareInterface
{

    use LoggerTrait;

    /**
     * @var SystemManager
     */
    protected $manager;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    protected $systemInstallRepository;

    /**
     * @var CurlSenderFactory
     */
    protected $factory;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var SystemLoader
     */
    private $loader;

    /**
     * CMEventActivator constructor.
     *
     * @param SystemManager     $manager
     * @param DocumentManager   $dm
     * @param CurlSenderFactory $factory
     * @param SystemLoader      $loader
     */
    function __construct(
        SystemManager $manager,
        DocumentManager $dm,
        CurlSenderFactory $factory,
        SystemLoader $loader
    )
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->manager                 = $manager;
        $this->factory                 = $factory;
        $this->dm                      = $dm;
        $this->loader                  = $loader;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        throw new RuntimeException('The process method is not implemented.');
    }

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     * @throws SystemException
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        /** @var CMEventSystemInterface $system */
        $system = $this->manager->getSystem(CMHeaders::get(CMHeaders::SYSTEM_KEY, $dto->getHeaders()) ?? '');
        $data   = json_decode($dto->getData(), TRUE);

        $sender        = $this->factory->create($loop);
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());

        /** @var RequesterInterface $requester */
        $requester = $system->getCMEventRequester($systemInstall);

        $requests = [];
        $results  = [];
        foreach ($data as $index => $eventKey) {
            $event = $system->getEventObject($eventKey);

            $requests[] = $this->processField($sender, $requester, $event, $systemInstall, $index, $callbackItem,
                $results);
        }

        $promise = all($requests);

        $this->dm->flush();

        return $promise;
    }

    /**
     * @param CurlSender         $sender
     * @param RequesterInterface $requester
     * @param CMEventObject      $event
     * @param SystemInstall      $systemInstall
     * @param int                $index
     * @param callable           $callbackItem
     * @param array              $results
     *
     * @return PromiseInterface
     */
    protected function processField(
        CurlSender $sender,
        RequesterInterface $requester,
        CMEventObject $event,
        SystemInstall $systemInstall,
        int $index,
        callable $callbackItem,
        array &$results
    ): PromiseInterface
    {
        return $this
            ->fetchData($sender, $requester->getRequestDto([RequesterInterface::OBJECT => $event]))
            ->then(
                function (ResponseInterface $response) use (
                    $requester,
                    $index,
                    $systemInstall,
                    $event,
                    $results
                ): SuccessMessage {
                    $responseDto = $this->createDtoFromResponse($response);
                    $requester->processResponse($responseDto, $systemInstall);

                    $done = $systemInstall->getEventState($event->getEvent());

                    $res       = new ResultDto(ResultDto::statusFromBool($done), $event->getEvent(),
                        sprintf('%s custom field status', $systemInstall->getSystem()));
                    $results[] = $res;

                    return $this->createSuccessMessage($res, $index);
                },
                function (ResponseException $e) use ($systemInstall, $index): SuccessMessage {
                    return $this->batchConnectorError($e, $this->getSystem($systemInstall), $systemInstall, $index);
                })
            ->then($callbackItem);
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemInterface
     * @throws SystemException
     */
    protected function getSystem(SystemInstall $systemInstall): SystemInterface
    {
        return $this->loader->getSystem($systemInstall->getSystem());
    }

    /**
     * @param ResponseInterface $response
     *
     * @return ResponseDto
     */
    protected function createDtoFromResponse(ResponseInterface $response): ResponseDto
    {
        return new ResponseDto(
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            $response->getBody()->getContents(),
            $response->getHeaders()
        );
    }

    /**
     * @param CurlSender $sender
     * @param RequestDto $dto
     *
     * @return PromiseInterface
     */
    protected function fetchData(CurlSender $sender, RequestDto $dto): PromiseInterface
    {
        return $sender->send($dto);
    }

    /**
     * @param ResultDto $dto
     * @param int       $i
     *
     * @return SuccessMessage
     */
    protected function createSuccessMessage(ResultDto $dto, int $i): SuccessMessage
    {
        $successMessage = new SuccessMessage($i);
        $successMessage->setData(sprintf('%s, status: %s, action: %s.',
            $dto->getMessage(),
            $dto->getStatus(),
            $dto->getAction()
        ));

        return $successMessage;
    }

}