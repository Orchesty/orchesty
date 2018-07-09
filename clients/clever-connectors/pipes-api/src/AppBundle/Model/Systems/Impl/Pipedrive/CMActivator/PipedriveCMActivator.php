<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\CMActivator;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventActivator;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventSystemInterface;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Requester\PipedriveCMEventRequester;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Clue\React\Buzz\Message\ResponseException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;

/**
 * Class PipedriveCMActivator
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\CMActivator
 */
class PipedriveCMActivator extends CMEventActivator
{

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     * @throws SystemException
     * @throws CleverConnectorsException
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        /** @var CMEventSystemInterface $system */
        $system = $this->manager->getSystem(CMHeaders::get(CMHeaders::SYSTEM_KEY, $dto->getHeaders()) ?? '');
        $data   = json_decode($dto->getData(), TRUE);

        $sender        = $this->factory->create($loop);
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());

        /** @var PipedriveCMEventRequester $requester */
        $requester = $system->getCMEventRequester($systemInstall);

        $results = [];
        $dto     = $requester->getListRequestDto();
        $promise = $this->fetchData($sender, $dto)
            ->then(function (ResponseInterface $response) use (&$data, $requester) {
                $responseDto = $this->createDtoFromResponse($response);

                return $requester->processListResponse($data, $responseDto);
            },
                function (ResponseException $e) use ($systemInstall): SuccessMessage {
                    return $this->batchConnectorError($e, $this->getSystem($systemInstall), $systemInstall, 1);
                })
            ->then(function (array $fields) use (
                $sender,
                $requester,
                $systemInstall,
                $system,
                $callbackItem,
                &$results
            ) {
                $requests = [];
                foreach ($fields as $index => $eventKey) {
                    $event = $system->getEventObject($eventKey);

                    $requests[] = $this->processField($sender, $requester, $event, $systemInstall, $index,
                        $callbackItem, $results);
                }
                $promise = all($requests);
                $this->dm->flush();

                return $promise;
            });

        return $promise;
    }

}