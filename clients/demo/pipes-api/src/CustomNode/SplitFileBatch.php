<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/16/18
 * Time: 9:56 AM
 */

namespace Demo\CustomNode;

use DateTime;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class SplitFileBatch
 *
 * @package Demo\CustomNode
 */
class SplitFileBatch implements BatchInterface, CustomNodeInterface
{

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
        $data = json_decode($dto->getData(), TRUE);

        if (array_key_exists('data', $data)) {
            $data = json_decode($data['data'], TRUE);

            $datetime = new DateTime();
            if ($datetime->getTimestamp() % 2 == 0) {
                unset($data['bids']);
            } else {
                unset($data['asks']);
            }

            return resolve()
                ->then(function () use ($data) {
                    return (new SuccessMessage(0))->setData((string) json_encode($data));
                })
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