<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\LongRunningNode\Consumer;

use Bunny\Message;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Loader\LongRunningNodeLoader;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeManager;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Consumer\CallbackInterface;
use Throwable;

/**
 * Class LongRunningNodeCallback
 *
 * @package Hanaboso\PipesPhpSdk\LongRunningNode\Consumer
 */
class LongRunningNodeCallback implements CallbackInterface
{

    /**
     * @var LongRunningNodeManager
     */
    private $manager;

    /**
     * @var LongRunningNodeLoader
     */
    private $loader;

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
     * @param Message    $message
     * @param Connection $connection
     * @param int        $channelId
     *
     * @throws OnRepeatException
     */
    public function processMessage(Message $message, Connection $connection, int $channelId): void
    {
        try {
            $this->manager->saveDocument(
                $this->loader
                    ->getLongRunningNode($message->getHeader(PipesHeaders::createKey(PipesHeaders::NODE_NAME)))
                    ->beforeAction($message)
            );

            $connection->getChannel($channelId)->ack($message);
        } catch (Throwable $t) {
            throw new OnRepeatException(
                (new ProcessDto())->setData($message->content)->setHeaders($message->headers),
                $t->getMessage(),
                $t->getCode(),
                $t
            );
        }
    }

}
