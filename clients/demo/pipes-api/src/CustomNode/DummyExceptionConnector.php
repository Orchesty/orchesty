<?php declare(strict_types=1);

namespace Demo\CustomNode;

use Exception;
use Hanaboso\CommonsBundle\Monolog\LoggerContext;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Exception\CustomNodeException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class DummyExceptionConnector
 *
 * @package Demo\CustomNode
 */
class DummyExceptionConnector extends CustomNodeAbstract implements LoggerAwareInterface
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * DummyExceptionConnector constructor.
     */
    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     *
     * @throws Exception
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        if (mt_rand(1, 10) == 5) {
            try {
                $this->throwDummyException();
            } catch (Exception $e) {
                $context = new LoggerContext();
                $context
                    ->setHeaders($dto)
                    ->setException($e);
                $this->logger->error($e->getMessage(), $context->toArray());

                $dto->addHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE), '1003');
            }
        }

        return $dto;
    }

    /**
     * @throws Exception
     */
    private function throwDummyException(): void
    {
        $words    = ['Lorem', 'ipsumdolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit'];
        $wordsCnt = rand(3, 6);
        $text     = '';

        for ($i = 1; $i <= $wordsCnt; $i++) {
            $text .= sprintf('%s ', $words[rand(0, 6)]);
        }

        throw new CustomNodeException(sprintf('%sexception', ucfirst(strtolower($text))));
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return DummyExceptionConnector
     */
    public function setLogger(LoggerInterface $logger): DummyExceptionConnector
    {
        $this->logger = $logger;

        return $this;
    }

}
