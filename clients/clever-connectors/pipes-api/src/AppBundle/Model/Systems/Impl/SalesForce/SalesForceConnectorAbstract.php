<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use Clue\React\Buzz\Browser;
use DateTime;
use Exception;
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;

/**
 * Class SalesForceConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce
 */
abstract class SalesForceConnectorAbstract implements BatchInterface, CustomNodeInterface
{

    protected const QUERY_URL  = '%sservices/data/v40.0/query?q=%s';
    protected const PAGE_LIMIT = 50;

    /**
     * @var SalesForceSystem
     */
    protected $system;

    /**
     * SalesForceUpdateConnector constructor.
     *
     * @param SalesForceSystem $system
     */
    public function __construct(SalesForceSystem $system)
    {
        $this->system = $system;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws Exception
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('SalesForce has not implemented "process" function.');
    }

    /**
     * @param DateTime|null $from
     * @param DateTime      $to
     *
     * @return string
     */
    protected function getTimeQuery(?DateTime $from, DateTime $to): string
    {
        $timeQuery = '';

        if ($from) {
            $timeQuery = ltrim(http_build_query(['q' => '+where+LastModifiedDate>' . $from->format(DateTime::ISO8601)]),
                'q=');
        }
        $timeQuery .= ($timeQuery === '' ? '' : '+and') .
            ltrim(http_build_query(['q' => '+where+LastModifiedDate<=' . $to->format(DateTime::ISO8601)]), 'q=');

        return $timeQuery;
    }

    /**
     * @param string $baseUrl
     * @param array  $headers
     * @param string $timeQuery
     *
     * @return RequestInterface
     */
    protected function createCountRequest(string $baseUrl, array $headers, string $timeQuery = ''): RequestInterface
    {
        $query = 'select+count()+from+contact' . $timeQuery;

        return new Request('GET', sprintf(static::QUERY_URL, $baseUrl, $query), $headers);
    }

    /**
     * @param Browser          $browser
     * @param RequestInterface $request
     *
     * @return PromiseInterface
     */
    protected function fetchData(Browser $browser, RequestInterface $request): PromiseInterface
    {
        return $browser->send($request);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return SystemInstall
     */
    protected function getSystemInstall(ProcessDto $dto): SystemInstall
    {
        return SystemInstall::from(json_decode($dto->getData(), TRUE));
    }

    /**
     * @param ResponseInterface $response
     * @param int               $page
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    protected function createSuccessMessage(ResponseInterface $response, int $page): SuccessMessage
    {
        $res = json_decode($response->getBody()->getContents(), TRUE);
        if (is_array($res) && array_key_exists('records', $res)) {
            $successMessage = new SuccessMessage($page);
            $successMessage->setData(json_encode($res['records']));
            unset($res);

            return $successMessage;
        }

        throw new SystemException(
            'Missing [records] key in response data from SalesForce.',
            SystemException::MISSING_RESPONSE_DATA
        );
    }

    /**
     * @param ResponseInterface $response
     *
     * @return int
     * @throws SystemException
     */
    protected function getTotalPages(ResponseInterface $response): int
    {
        $data = json_decode($response->getBody()->getContents(), TRUE);

        if (!is_array($data) || !array_key_exists('totalSize', $data)) {
            throw new SystemException(
                'SalesForce response has no "totalSize" field!',
                SystemException::MISSING_RESPONSE_DATA
            );
        }

        $total = (int) ceil($data['totalSize'] / self::PAGE_LIMIT);
        unset($data);

        return $total;
    }

}