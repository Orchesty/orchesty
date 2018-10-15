<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 7.11.17
 * Time: 14:03
 */

namespace CleverConnectors\AppBundle\Model\CM\TestRepeaterConnector;

use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Predis\Client;

/**
 * Class CMTestRepeaterConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\TestRepeaterConnector
 */
class CMTestRepeaterConnector implements ConnectorInterface
{

    /**
     * @var Client
     */
    private $client;

    /**
     * CMTestRepeaterConnector constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'test-repeater-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        return $this->process($dto);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        return $this->process($dto);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    protected function process(ProcessDto $dto): ProcessDto
    {
        $processId = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders());
        $key       = sprintf('%s:repeatertest', $processId);

        $hop = intval($this->client->hget($key, 'times'));
        if (!$hop) {
            $hop = 0;
        }

        $this->client->hset($key, 'times', ++$hop);

        if ($hop == 5) {
            $dto->addHeader(CMHeaders::createKey(CMHeaders::RESULT_CODE), '0');

            return $dto;
        }

        $dto->addHeader(CMHeaders::createKey(CMHeaders::RESULT_CODE), '1001');
        $dto->addHeader(CMHeaders::createKey(CMHeaders::REPEAT_INTERVAL), '2000');
        $dto->addHeader(CMHeaders::createKey(CMHeaders::REPEAT_HOPS), (string) $hop);
        $dto->addHeader(CMHeaders::createKey(CMHeaders::REPEAT_MAX_HOPS), '5');

        return $dto;
    }

}