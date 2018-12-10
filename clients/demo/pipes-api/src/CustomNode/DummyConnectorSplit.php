<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/26/18
 * Time: 4:34 PM
 */

namespace Demo\CustomNode;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class IdnesConnector
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

            return $dto->addHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE), "1003");
        }

        return $dto;
    }

}