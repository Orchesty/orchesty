<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Repository;

use Hanaboso\CommonsBundle\WorkerApi\ClientInterface;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Storage\Mongodb\Repository;

/**
 * Class WebhookRepository
 *
 * @extends Repository<Webhook>
 *
 * @package Hanaboso\PipesPhpSdk\Application\Repository
 */
final class WebhookRepository extends Repository
{

    /**
     * WebhookRepository constructor.
     *
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        parent::__construct($client, Webhook::class);
    }

}
