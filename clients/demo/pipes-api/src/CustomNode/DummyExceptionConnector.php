<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/26/18
 * Time: 4:34 PM
 */

namespace Demo\CustomNode;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Exception\CustomNodeException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class IdnesConnector
 *
 * @package Demo\CustomNode
 */
class DummyExceptionConnector implements CustomNodeInterface, LoggerAwareInterface
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
                $this->logger->error($e->getMessage(), ['Exception' => $e]);

                $dto->addHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE), "1003");
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
            $text .= $words[rand(0, 6)] . ' ';
        }

        throw new CustomNodeException(ucfirst(strtolower($text)) . 'exception');
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
