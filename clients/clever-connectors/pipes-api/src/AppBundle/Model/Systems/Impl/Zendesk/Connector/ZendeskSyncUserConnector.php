<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector;

use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class ZendeskSyncUserConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector
 */
class ZendeskSyncUserConnector extends ZendeskUserConnectorAbstract
{

    private const USERS_URL = 'api/v2/users.json?per_page=' . self::PER_PAGE;

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'zendesk-sync-user-connector';
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
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->system->getRequestDto($systemInstall, 'GET');
        $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        $url       = new Uri(rtrim($requestDto->getUri(TRUE)) . self::USERS_URL);
        $processId = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders());
        $promise   = $this->getPage($sender, $callbackItem, RequestDto::from($requestDto, $url), 1, $processId);

        $this->systemInstallRepository->setSyncTime($systemInstall);

        return $promise;
    }

    /**
     * @param mixed $data
     * @param int   $page
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    protected function createSuccessMessage($data, int $page): SuccessMessage
    {
        if (array_key_exists('users', $data)) {
            $successMessage = new SuccessMessage($page);
            $successMessage->setData(json_encode($data['users']));
            unset($data);

            return $successMessage;
        } else {
            throw new SystemException(
                'Bad response data for Zendesk sync request.',
                SystemException::MISSING_RESPONSE_DATA
            );
        }
    }

}