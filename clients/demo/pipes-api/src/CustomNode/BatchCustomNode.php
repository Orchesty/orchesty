<?php declare(strict_types=1);

namespace Demo\CustomNode;

use GuzzleHttp\Promise\PromiseInterface;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchTrait;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use Hanaboso\Utils\String\Json;

/**
 * Class BatchCustomNode
 *
 * @package Demo\CustomNode
 */
final class BatchCustomNode extends CustomNodeAbstract implements BatchInterface
{

    use BatchTrait;

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        return $dto;
    }

    /**
     * @param ProcessDto $dto
     * @param callable   $callbackItem
     *
     * @return PromiseInterface
     */
    public function processBatch(ProcessDto $dto, callable $callbackItem): PromiseInterface
    {
        $messages = Json::decode($dto->getData())['messages'] ?? 10;

        for ($i = 0; $i < $messages; $i++) {
            $callbackItem((new SuccessMessage($i))->setData($dto->getData()));
        }

        return $this->createPromise();
    }

}
