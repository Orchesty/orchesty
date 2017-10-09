<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use Clue\React\Buzz\Browser;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use GuzzleHttp\Psr7\Request;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Promise\Promise;

/**
 * Class SalesForceConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce
 */
abstract class SalesForceConnectorAbstract implements BatchInterface, CustomNodeInterface
{

    protected const QUERY_URL  = '%sservices/data/v40.0/query​​​?q=%s';
    protected const PAGE_LIMIT = 50;

    /**
     * @var SalesForceSystem
     */
    protected $system;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * SalesForceUpdateConnector constructor.
     *
     * @param SalesForceSystem $system
     * @param DocumentManager  $dm
     */
    public function __construct(SalesForceSystem $system, DocumentManager $dm)
    {
        $this->system = $system;
        $this->dm     = $dm;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws Exception
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        throw new Exception('Not implemented');
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
            $timeQuery = '+where+LastModifiedDate>' . $from->format(DateTime::ISO8601);
        }
        $timeQuery .= ($timeQuery === '' ? '' : '+and') .
            '+where+LastModifiedDate<=' . $to->format(DateTime::ISO8601);

        return $timeQuery;
    }

    /**
     * @param string $baseUrl
     * @param string $timeQuery
     *
     * @return RequestInterface
     */
    protected function createCountRequest(string $baseUrl, string $timeQuery): RequestInterface
    {
        $query = 'select+count()+from+contact' . $timeQuery;

        return new Request('GET', sprintf(static::QUERY_URL, $baseUrl, $query));
    }

    /**
     * @param Browser          $browser
     * @param RequestInterface $request
     *
     * @return Promise
     */
    protected function fetchData(Browser $browser, RequestInterface $request): Promise
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
        if (array_key_exists('records', $res)) {
            $successMessage = new SuccessMessage($page);
            $successMessage->setData($res['records']);
            unset($res);

            return $successMessage;
        }

        throw new SystemException(
            'Missing [records] key in response data from SalesForce.',
            SystemException::MISSING_RESPONSE_DATA
        );
    }

}