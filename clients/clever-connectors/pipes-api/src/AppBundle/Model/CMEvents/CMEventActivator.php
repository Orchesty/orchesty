<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CMEvents;

use CleverConnectors\AppBundle\Amq\CMActivatorProducer;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Requester\ResultDto;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use RuntimeException;
use function React\Promise\all;

/**
 * Class CMEventActivator
 *
 * @package CleverConnectors\AppBundle\Model\CMEvents
 */
class CMEventActivator implements BatchInterface, CustomNodeInterface
{

    /**
     * @var SystemManager
     */
    private $manager;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * @var CurlSenderFactory
     */
    private $factory;

    /**
     * @var CMActivatorProducer
     */
    private $streamProducer;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * CMEventActivator constructor.
     *
     * @param SystemManager       $manager
     * @param DocumentManager     $dm
     * @param CurlSenderFactory   $factory
     * @param CMActivatorProducer $streamProducer
     */
    function __construct(SystemManager $manager, DocumentManager $dm, CurlSenderFactory $factory,
                         CMActivatorProducer $streamProducer)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->manager                 = $manager;
        $this->factory                 = $factory;
        $this->streamProducer          = $streamProducer;
        $this->dm                      = $dm;
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

            $requests[] = $this
                ->fetchData($sender, $requester->getRequestDto([RequesterInterface::OBJECT => $event]))
                ->then(
                    function (ResponseInterface $response) use ($requester, $index, $systemInstall, $event, $results
                    ): SuccessMessage {
                        $responseDto = new ResponseDto(
                            $response->getStatusCode(),
                            $response->getReasonPhrase(),
                            $response->getBody()->getContents(),
                            $response->getHeaders()
                        );

                        $requester->processResponse($responseDto, $systemInstall);

                        $done = $systemInstall->getEventState($event->getEvent());

                        $res  = new ResultDto(ResultDto::statusFromBool($done), $event->getEvent(),
                            sprintf('%s custom field status', $systemInstall->getSystem()));
                        $results[] = $res;

                        return $this->createSuccessMessage($res, $index);
                    })
                ->then($callbackItem);
        }

        $promise = all($requests);

        $this->dm->flush();

        $this->streamProducer->publish([
            'event'   => 'event-activator',
            'groups'  => $systemInstall->getUser(),
            'content' => json_encode($results),
        ]);

        return $promise;
    }

    /**
     * @param CurlSender $sender
     * @param RequestDto $dto
     *
     * @return PromiseInterface
     */
    private function fetchData(CurlSender $sender, RequestDto $dto): PromiseInterface
    {
        return $sender->send($dto);
    }

    /**
     * @param ResultDto $dto
     * @param int       $i
     *
     * @return SuccessMessage
     */
    private function createSuccessMessage(ResultDto $dto, int $i): SuccessMessage
    {
        $successMessage = new SuccessMessage($i);
        $successMessage->setData(sprintf('%s, status: %s, action: %s.',
            $dto->getMessage(),
            $dto->getStatus(),
            $dto->getAction()
        ));

        return $successMessage;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        throw new RuntimeException('The process method is not implemented.');
    }

}