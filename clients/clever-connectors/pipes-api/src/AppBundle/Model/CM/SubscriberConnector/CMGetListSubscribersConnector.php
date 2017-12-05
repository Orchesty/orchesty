<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SubscriberConnector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class CMGetListSubscribersConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\SubscriberConnector
 */
class CMGetListSubscribersConnector extends CMGetSubscribersConnectorAbstract
{

    /**
     * @var string
     */
    private $listId;

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
        $sender        = $this->factory->create($loop, $this->secret);
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());

        // TODO get distribution list from settings
        // TODO finish tests (live and unit)
        //        $settings = $systemInstall->getSettings();
        //        if (!isset($settings[SystemInstall::DISTRIBUTION_LIST])) {
        //            throw new CleverConnectorsException();
        //        }
        //
        //        $this->listId = $settings[SystemInstall::DISTRIBUTION_LIST];

        $req = new RequestDto(CurlManager::METHOD_GET, new Uri($this->getUrl(0)));
        $req->setHeaders($this->getAuthorizationHeaders($systemInstall->getUser(), $systemInstall->getToken()));

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
        return sprintf(
            '%s/lists/%s/subscribers/?offset=%s&count=%s',
            self::BASE_URL,
            $this->listId,
            $offset,
            self::COUNT
        );
    }

}