<?php declare(strict_types=1);

namespace Demo\CustomNode;

use Exception;
use Hanaboso\CommonsBundle\Monolog\LoggerContext;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Exception\CustomNodeException;
use Hanaboso\Utils\String\Json;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Class FilterStockExchange
 *
 * @package Demo\CustomNode
 */
final class FilterStockExchange extends CustomNodeAbstract implements LoggerAwareInterface
{

    /**
     * @var string
     */
    private string $key;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * FilterStockExchange constructor.
     *
     * @param string $key
     */
    public function __construct(string $key)
    {
        $this->key    = $key;
        $this->logger = new NullLogger();
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws Exception
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData());

        if (array_key_exists($this->key, $data)) {
            return $dto->setData(Json::encode($data[$this->key]));
        }

        try {
            if (mt_rand(1, 10) == 5) {
                throw new CustomNodeException('My test error exception');
            }
        } catch (Throwable $t) {
            $context = new LoggerContext();
            $context
                ->setException($t)
                ->setHeaders($dto);

            $this->logger->error($t->getMessage(), $context->toArray());
        }

        $dto->setData('');
        $dto->setStopProcess(ProcessDto::DO_NOT_CONTINUE);

        return $dto;
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return FilterStockExchange
     */
    public function setLogger(LoggerInterface $logger): FilterStockExchange
    {
        $this->logger = $logger;

        return $this;
    }

}
