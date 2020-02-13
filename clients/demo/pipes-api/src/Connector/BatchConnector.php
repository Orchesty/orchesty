<?php declare(strict_types=1);

namespace Demo\Connector;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessActionNotSupportedTrait;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
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

    use ProcessActionNotSupportedTrait;
    use ProcessEventNotSupportedTrait;

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'batch';
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
