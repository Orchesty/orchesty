<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 3.10.17
 * Time: 12:33
 */

namespace CleverConnectors\AppBundle\Model\CM\TestForwardConnector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use GuzzleHttp\Exception\ConnectException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;

/**
 * Class CMTestForwardConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\TestForwardConnector
 */
class CMTestForwardConnector implements ConnectorInterface
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'test-forward-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        return $this->process($dto);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        return $this->process($dto);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    protected function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);
        if (!isset($data['data'])) {
            throw new CleverConnectorsException(
                'Required data field.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $data = $data['data'];
        $dto->setData(json_encode($data));

        return $dto;
    }

}
