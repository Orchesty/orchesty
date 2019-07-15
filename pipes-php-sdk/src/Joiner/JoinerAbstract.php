<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Joiner;

/**
 * Class JoinerAbstract
 *
 * @package Hanaboso\PipesPhpSdk\Joiner
 */
abstract class JoinerAbstract implements JoinerInterface
{

    /**
     * @param array $data
     * @param int   $count
     *
     * @return string[]
     */
    public function process(array $data, int $count): array
    {
        $this->save($data);

        $res = ['Incomplete data'];
        if ($this->isDataComplete($count)) {
            $res = $this->runCallback();
        }

        return $res;
    }

}
