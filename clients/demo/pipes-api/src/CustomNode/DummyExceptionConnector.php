<?php declare(strict_types=1);

namespace Demo\CustomNode;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Exception\CustomNodeException;
use Hanaboso\Utils\System\PipesHeaders;
use Hanaboso\Utils\Traits\LoggerTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class DummyExceptionConnector
 *
 * @package Demo\CustomNode
 */
final class DummyExceptionConnector extends CommonNodeAbstract implements LoggerAwareInterface
{

    use LoggerTrait;

    public const string NAME = 'dummy-exception-connector';

    /**
     * DummyExceptionConnector constructor.
     *
     * @param ApplicationInstallRepository $repository
     */
    public function __construct(ApplicationInstallRepository $repository)
    {
        parent::__construct($repository);

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
     *
     * @throws Exception
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        if (mt_rand(1, 10) == 5) {
            try {
                $this->throwDummyException();
            } catch (Exception $e) {
                $this->logger->error($e->getMessage(), []);

                $dto->addHeader(PipesHeaders::RESULT_CODE, '1003');
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

}
