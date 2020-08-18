<?php declare(strict_types=1);

namespace Demo\CustomNode;

use GuzzleHttp\Promise\PromiseInterface;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchTrait;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\String\Json;

/**
 * Class SplitFileBatch
 *
 * @package Demo\CustomNode
 */
final class SplitFileBatch extends CustomNodeAbstract implements BatchInterface
{

    use BatchTrait;

    /**
     * @param ProcessDto $dto
     * @param callable   $callbackItem
     *
     * @return PromiseInterface
     * @throws DateTimeException
     */
    public function processBatch(ProcessDto $dto, callable $callbackItem): PromiseInterface
    {
        $data = Json::decode($dto->getData());

        if (array_key_exists('data', $data)) {
            $datetime = DateTimeUtils::getUtcDateTime();
            if ($datetime->getTimestamp() % 2 == 0) {
                unset($data['bids']);
            } else {
                unset($data['asks']);
            }

            return $this->createPromise(static fn() => (new SuccessMessage(0))->setData(Json::encode($data)))
                ->then(static fn() => (new SuccessMessage(0))->setData(Json::encode($data)))
                ->then($callbackItem);
        }

        return $this->createPromise();
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        return $dto;
    }

}
