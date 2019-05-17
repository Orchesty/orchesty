<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\LongRunningNode\Consumer;

use Bunny\Message;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\ApiGateway\Exceptions\OnRepeatException;
use Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Loader\LongRunningNodeLoader;
use Hanaboso\PipesFramework\LongRunningNode\Model\LongRunningNodeManager;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Consumer\CallbackInterface;
use Throwable;

/**
 * Class LongRunningNodeCallback
 *
 * @package Hanaboso\PipesFramework\LongRunningNode\Consumer
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
