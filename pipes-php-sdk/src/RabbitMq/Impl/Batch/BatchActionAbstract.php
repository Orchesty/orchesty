<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch;

use Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory;
use Hanaboso\Utils\System\PipesHeaders;
use InvalidArgumentException;
use PhpAmqpLib\Message\AMQPMessage;
use RabbitMqBundle\Utils\Message;

/**
 * Class BatchActionAbstract
 *
 * @package Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch
 */
abstract class BatchActionAbstract implements BatchActionInterface
{

    use BatchTrait;

    /**
     * @param AMQPMessage $message
     * @param callable    $itemCallBack
     */
    public function batchAction(AMQPMessage $message, callable $itemCallBack): void
    {
        $headers = Message::getHeaders($message);

        if ($this->isEmpty(PipesHeaders::get(PipesHeaders::NODE_NAME, $headers))) {
            throw new InvalidArgumentException(
                sprintf('Missing "%s" in the message header.', PipesHeaders::NODE_NAME),
            );
        }

        $this
            ->getBatchService(PipesHeaders::get(PipesHeaders::NODE_NAME, $headers) ?? '')
            ->processBatch(ProcessDtoFactory::createFromMessage($message), $itemCallBack)->wait();
    }

    /**
     * @param string|null $value
     *
     * @return bool
     */
    private function isEmpty(?string $value): bool
    {
        return $value === '' || $value === NULL;
    }

}
