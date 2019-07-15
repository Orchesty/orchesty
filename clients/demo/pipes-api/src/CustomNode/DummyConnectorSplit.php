<?php declare(strict_types=1);

namespace Demo\CustomNode;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeInterface;

/**
 * Class DummyConnectorSplit
 *
 * @package Demo\CustomNode
 */
class DummyConnectorSplit implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws DateTimeException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        if (DateTimeUtils::getUTCDateTime()->getTimestamp() % 2 == 0) {
            $dto->setData('');

            return $dto->addHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE), '1003');
        }

        return $dto;
    }

}
