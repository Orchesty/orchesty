<?php declare(strict_types=1);

namespace Demo\CustomNode;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\System\PipesHeaders;

/**
 * Class DummyConnectorSplit
 *
 * @package Demo\CustomNode
 */
final class DummyConnectorSplit extends CommonNodeAbstract
{

    public const NAME = 'send-connector-split';

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
     * @throws DateTimeException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        if (DateTimeUtils::getUtcDateTime()->getTimestamp() % 2 == 0) {
            $dto->setData('');

            return $dto->addHeader(PipesHeaders::RESULT_CODE, '1003');
        }

        return $dto;
    }

}
