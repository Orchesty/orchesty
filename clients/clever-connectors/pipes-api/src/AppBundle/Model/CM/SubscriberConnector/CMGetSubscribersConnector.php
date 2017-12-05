<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SubscriberConnector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class CMGetSubscribersConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\SubscriberConnector
 */
class CMGetSubscribersConnector extends CMGetSubscribersConnectorAbstract
{

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

        $req = new RequestDto(CurlManager::METHOD_GET, new Uri($this->getUrl(0)));
        $req->setHeaders($this->getAuthorizationHeaders($user, $token));

        $promise = $this->getPage($sender, $callbackItem, $req);

        return $promise;
    }

    /**
     * @param int $offset
     *
     * @return string
     */
    protected function getUrl(int $offset): string
    {
        return sprintf('%s/subscribers/?offset=%s&count=%s', self::BASE_URL, $offset, self::COUNT);
    }

}