<?php declare(strict_types=1);

namespace Demo\CustomNode;

use EmailServiceBundle\Utils\PipesHeaders;
use Exception;
use Hanaboso\CommonsBundle\Monolog\LoggerContext;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeInterface;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Exception\CustomNodeException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Class FilterStockExchange
 *
 * @package Demo\CustomNode
 */
class FilterStockExchange implements CustomNodeInterface, LoggerAwareInterface
{

    /**
     * @var string
     */
    private $key;

    /**
     * @var LoggerInterface
     */
    private $logger;

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
        $data = json_decode($dto->getData(), TRUE);

        if (array_key_exists($this->key, $data)) {
            return $dto->setData((string) json_encode($data[$this->key]));
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

        return $dto->addHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE), '1003');
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
