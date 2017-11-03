<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11/3/17
 * Time: 10:24 AM
 */

namespace CleverConnectors\AppBundle\Model\ProgressCounter\Publisher;

use Bunny\Client;
use Hanaboso\PipesFramework\RabbitMq\BunnyManager;

/**
 * Class AsyncStorage
 *
 * @package CleverConnectors\AppBundle\Model\ProgressCounter\Storage
 */
class AsyncPublisher implements IProgressPublisher
{

    private const STREAM_QUEUE = 'pipes.stream';

    /**
     * @var BunnyManager
     */
    private $bunnyManager;

    /**
     * AsyncStorage constructor.
     *
     * @param BunnyManager $bunnyManager
     *
     * @internal param Client $client
     */
    public function __construct(BunnyManager $bunnyManager)
    {
        $this->bunnyManager = $bunnyManager;
    }

    /**
     * @return Client
     */
    private function getClient(): Client
    {
        $client = new Client($this->bunnyManager->getConfig());
        $client->connect();

        return $client;
    }

    /**
     * @param array $data
     */
    public function publish(array $data): void
    {
        $client = $this->getClient();
        $client->channel()->publish(json_encode($data), ['application/json'], '', self::STREAM_QUEUE);
        $client->disconnect();
    }

}