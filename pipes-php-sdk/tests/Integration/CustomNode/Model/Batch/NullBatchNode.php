<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\CustomNode\Model\Batch;

use GuzzleHttp\Promise\PromiseInterface;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchTrait;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;

/**
 * Class NullBatchNode
 *
 * @package PipesPhpSdkTests\Integration\CustomNode\Model\Batch
 */
final class NullBatchNode implements BatchInterface, CustomNodeInterface
{

    use BatchTrait;

    /**
     * @param ProcessDto $dto
     * @param callable   $callbackItem
     *
     * @return PromiseInterface
     */
    public function processBatch(ProcessDto $dto, callable $callbackItem): PromiseInterface
    {
        $p = $callbackItem(new SuccessMessage(1));
        $p->resolve($dto->getData());

        return $this->createPromise();
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

    /**
     * @param ApplicationInterface $application
     *
     * @return CustomNodeInterface
     */
    public function setApplication(ApplicationInterface $application): CustomNodeInterface
    {
        $application;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getApplicationKey(): ?string
    {
        return 'null-batch-key';
    }

}
