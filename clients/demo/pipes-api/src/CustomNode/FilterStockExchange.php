<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/14/18
 * Time: 2:46 PM
 */

namespace Demo\CustomNode;

use EmailServiceBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class FilterBid
 *
 * @package App\CustomNode
 */
class FilterStockExchange implements CustomNodeInterface
{

    /**
     * @var string
     */
    private $key;

    /**
     * FilterStockExchange constructor.
     *
     * @param string $key
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if (array_key_exists($this->key, $data)) {
            return $dto->setData(json_encode($data[$this->key]));
        }

        $dto->setData('');

        return $dto->addHeader(PipesHeaders::createKey(PipesHeaders::RESULT_CODE), "1003");
    }

}