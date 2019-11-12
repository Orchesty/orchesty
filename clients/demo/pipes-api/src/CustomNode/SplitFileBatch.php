<?php declare(strict_types=1);

namespace Demo\CustomNode;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class SplitFileBatch
 *
 * @package Demo\CustomNode
 */
class SplitFileBatch extends CustomNodeAbstract implements BatchInterface
{

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     * @throws DateTimeException
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $loop;
        $data = json_decode($dto->getData(), TRUE, 512, JSON_THROW_ON_ERROR);

        if (array_key_exists('data', $data)) {
            $data = json_decode($data['data'], TRUE, 512, JSON_THROW_ON_ERROR);

            $datetime = DateTimeUtils::getUTCDateTime();
            if ($datetime->getTimestamp() % 2 == 0) {
                unset($data['bids']);
            } else {
                unset($data['asks']);
            }

            return resolve()
                ->then(
                    function () use ($data) {
                        return (new SuccessMessage(0))->setData((string) json_encode($data, JSON_THROW_ON_ERROR));
                    }
                )
                ->then($callbackItem);
        }

        return resolve();
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
