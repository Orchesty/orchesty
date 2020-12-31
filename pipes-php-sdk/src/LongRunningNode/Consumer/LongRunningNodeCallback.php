<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\LongRunningNode\Consumer;

use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Loader\LongRunningNodeLoader;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeManager;
use Hanaboso\Utils\System\PipesHeaders;
use PhpAmqpLib\Message\AMQPMessage;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Consumer\CallbackInterface;
use RabbitMqBundle\Utils\Message;
use Throwable;

/**
 * Class LongRunningNodeCallback
 *
 * @package Hanaboso\PipesPhpSdk\LongRunningNode\Consumer
 */
final class LongRunningNodeCallback implements CallbackInterface
{

    /**
     * @var LongRunningNodeManager
     */
    private LongRunningNodeManager $manager;

    /**
     * @var LongRunningNodeLoader
     */
    private LongRunningNodeLoader $loader;

    /**
     * LongRunningNodeCallback constructor.
     *
     * @param LongRunningNodeManager $manager
     * @param LongRunningNodeLoader  $loader
     */
    public function __construct(LongRunningNodeManager $manager, LongRunningNodeLoader $loader)
    {
        $this->manager = $manager;
        $this->loader  = $loader;
    }

    /**
     * @param AMQPMessage $message
     * @param Connection  $connection
     * @param int         $channelId
     *
     * @throws OnRepeatException
     */
    public function processMessage(AMQPMessage $message, Connection $connection, int $channelId): void
    {
        $headers = Message::getHeaders($message);

        try {
            $this->manager->saveDocument(
                $this->loader
                    ->getLongRunningNode(PipesHeaders::get(PipesHeaders::NODE_NAME, $headers) ?? '')
                    ->beforeAction($message)
            );

            Message::ack($message, $connection, $channelId);
        } catch (Throwable $t) {
            throw new OnRepeatException(
                (new ProcessDto())->setData(Message::getBody($message))->setHeaders($headers),
                $t->getMessage(),
                $t->getCode(),
                $t
            );
        }
    }

}
