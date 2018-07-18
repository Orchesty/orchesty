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
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class IdnesConnector
 *
 * @package Demo\CustomNode
 */
class DummyExceptionConnector implements CustomNodeInterface
{

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
            $this->throwDummyException();
        }

        return $dto;
    }

    /**
     * @throws Exception
     */
    private function throwDummyException()
    {
        $words    = ['Lorem', 'ipsumdolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit'];
        $wordsCnt = rand(3, 6);
        $text     = '';

        for ($i = 1; $i <= $wordsCnt; $i++) {
            $text .= $words[rand(0, 6)] . ' ';
        }

        throw new Exception(ucfirst(strtolower($text)) . 'exception');
    }
}