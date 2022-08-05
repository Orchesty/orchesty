<?php declare(strict_types=1);

namespace Demo\CustomNode;

use Exception;
use Hanaboso\CommonsBundle\Monolog\LoggerContext;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract;
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
final class FilterStockExchange extends CommonNodeAbstract implements LoggerAwareInterface
{

    public const NAME = 'filter-stock-exchange';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * FilterStockExchange constructor.
     *
     * @param string $key
     */
    public function __construct(private string $key)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws Exception
     */
    public function processAction(ProcessDto $dto): ProcessDto
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
        $dto->setStopProcess(ProcessDto::DO_NOT_CONTINUE, '');

        return $dto;
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

}
