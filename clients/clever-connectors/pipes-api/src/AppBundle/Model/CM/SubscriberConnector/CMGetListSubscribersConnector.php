<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SubscriberConnector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
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

        $settings = $systemInstall->getSettings();
        if (!isset($settings[SystemInstall::DISTRIBUTION_LIST])) {
            throw new CleverConnectorsException(
                'Distribution list not found in settings',
                CleverConnectorsException::DISTRIBUTION_LIST_NOT_FOUND
            );
        }

        $this->listId = $settings[SystemInstall::DISTRIBUTION_LIST];
        $processId    = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders()) ?? '';
        $req          = new RequestDto(CurlManager::METHOD_GET, new Uri($this->getUrl(0)));
        $req->setHeaders($this->getAuthorizationHeaders($systemInstall->getUser(), $systemInstall->getToken()));

        $promise = $this->getPage($sender, $callbackItem, $req, 1, $processId);

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
            '%s/lists/%s/subscribers/?contact_status=1&offset=%s&count=%s',
            $this->getBaseUrl(),
            $this->listId,
            $offset,
            self::COUNT
        );
    }

}