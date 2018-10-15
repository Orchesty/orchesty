<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11/3/17
 * Time: 10:24 AM
 */

namespace CleverConnectors\AppBundle\Model\ProgressCounter\Publisher;

use Exception;
use Hanaboso\PipesFramework\RabbitMq\Producer\AbstractProducer;

/**
 * Class SyncStorage
 *
 * @package CleverConnectors\AppBundle\Model\ProgressCounter\Storage
 */
class SyncPublisher implements IProgressPublisher
{

    /**
     * @var AbstractProducer
     */
    private $producer;

    /**
     * SyncStorage constructor.
     *
     * @param AbstractProducer $producer
     */
    public function __construct(AbstractProducer $producer)
    {
        $this->producer = $producer;
    }

    /**
     * @param array $data
     *
     * @throws Exception
     */
    public function publish(array $data): void
    {
        $this->producer->publish($data);
    }

}