<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SubscriberConnector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CustomNode\Comparator;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class CMGetListSubscribersSocialConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\SubscriberConnector
 */
class CMGetListSubscribersSocialConnector extends CMGetSubscribersConnectorAbstract
{

    protected const COUNT = 100;

    /**
     * @var string
     */
    private $list;

    /**
     * @var array|string
     */
    private $passData;

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     * @throws CleverConnectorsException
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $sender = $this->factory->create($loop, $this->secret);

        $user   = CMHeaders::get(CMHeaders::GUID, $dto->getHeaders());
        $token  = CMHeaders::get(CMHeaders::TOKEN, $dto->getHeaders());
        $system = CMHeaders::get(CMHeaders::SYSTEM_KEY, $dto->getHeaders());

        if (!isset($user) || !isset($token) || !isset($system)) {
            throw new CleverConnectorsException(
                'User or Token or System is missing in header.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $data           = json_decode($dto->getData(), TRUE);
        $this->passData = $data;
        $subs           = [];

        if (array_key_exists('distribution_list', $data)) {
            $this->list = $data['distribution_list'];

            $processId = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders()) ?? '';
            $req       = new RequestDto(CurlManager::METHOD_GET, new Uri($this->getUrl(0)));
            $req->setHeaders($this->getAuthorizationHeaders($user, $token));

            $promise = $this->getPageSubs($sender, $callbackItem, $req, 1, $dto, $subs, $processId);
        } else {
            // Delete complete audience
            $this->createSuccessMessage($dto, $subs);
            $promise = resolve();
        }

        return $promise;
    }

    /**
     * @param CurlSender  $sender
     * @param callable    $callbackItem
     * @param RequestDto  $requestDto
     * @param int         $page
     * @param ProcessDto  $dto
     * @param array       $subs
     * @param null|string $processId
     *
     * @return PromiseInterface
     */
    protected function getPageSubs(
        CurlSender $sender,
        callable $callbackItem,
        RequestDto $requestDto,
        int $page = 1,
        ProcessDto $dto,
        array &$subs,
        ?string $processId = NULL
    ): PromiseInterface
    {
        $requestDto->setUri(new Uri($this->getUrl(($page - 1) * static::COUNT)));

        return $this->fetchData($sender, $requestDto)->then(
            function (ResponseInterface $response) use (
                $sender, $requestDto, $callbackItem, $page, $processId, $subs, $dto
            ) {
                if ($response->getStatusCode() === 200) {
                    $res = json_decode($response->getBody()->getContents(), TRUE);
                    foreach ($res as $sub) {
                        $subs[] = $this->subscriberCallback($sub['email']);
                    }
                    unset($res);

                    return $this->getPageSubs($sender, $callbackItem, $requestDto, $page + 1, $dto, $subs, $processId);
                } else {
                    if ($processId) {
                        $this->counterService->setTotal($processId, 1);
                    }

                    $callbackItem($this->createSuccessMessage($dto, $subs));

                    return resolve();
                }
            }
        );
    }

    /**
     * @param string $email
     *
     * @return string
     */
    protected function subscriberCallback(string $email): string
    {
        return $email;
    }

    /**
     * @param int $offset
     *
     * @return string
     */
    protected function getUrl(int $offset): string
    {
        return sprintf(
            '%s/lists/%s/subscribers/?contact_status=1&offset=%s&count=%s',
            $this->getBaseUrl(),
            $this->list,
            $offset,
            self::COUNT
        );
    }

    /**
     * @param ProcessDto $dto
     * @param array      $subs
     *
     * @return SuccessMessage
     */
    private function createSuccessMessage(ProcessDto $dto, array &$subs): SuccessMessage
    {
        $successMessage = new SuccessMessage(1);
        $successMessage->setData(json_encode([
            Comparator::KEY_SOURCE      => $subs,
            Comparator::KEY_DESTINATION => [],
            Comparator::KEY_PASS_DATA   => $this->passData,
        ]));
        unset($subs);

        return $successMessage;
    }

}