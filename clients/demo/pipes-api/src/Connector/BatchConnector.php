<?php declare(strict_types=1);

namespace Demo\Connector;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use Hanaboso\Utils\String\Json;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class BatchConnector
 *
 * @package Demo\Connector
 */
final class BatchConnector extends ConnectorAbstract implements BatchInterface
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'batch';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $dto;

        throw new ConnectorException(sprintf("Connector '%s': No support for Action!", $this->getId()));
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        $dto;

        throw new ConnectorException(sprintf("Connector '%s': No support for Event!", $this->getId()));
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
        $loop;
        $messages = Json::decode($dto->getData())['messages'] ?? 10;

        for ($i = 0; $i < $messages; $i++) {
            $callbackItem((new SuccessMessage($i))->setData($dto->getData()));
        }

        return resolve();
    }

}
