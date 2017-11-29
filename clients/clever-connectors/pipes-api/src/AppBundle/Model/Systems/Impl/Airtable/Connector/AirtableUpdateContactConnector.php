<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class AirtableUpdateContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector
 */
class AirtableUpdateContactConnector extends AirtableContactConnectorAbstract
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
        // TODO: Implement processBatch() method.
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'airtable-update-contact-connector';
    }

}