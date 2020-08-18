<?php declare(strict_types=1);

namespace Demo\CustomNode;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\System\PipesHeaders;

/**
 * Class DummyConnectorSplit
 *
 * @package Demo\CustomNode
 */
final class DummyConnectorSplit extends CustomNodeAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws DateTimeException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        if (DateTimeUtils::getUtcDateTime()->getTimestamp() % 2 == 0) {
            $dto->setData('');

            return $dto->addHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE), '1003');
        }

        return $dto;
    }

}
