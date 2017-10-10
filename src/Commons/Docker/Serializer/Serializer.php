<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: pavel.severyn
 * Date: 10.10.17
 * Time: 16:04
 */

namespace Hanaboso\PipesFramework\Commons\Docker\Serializer;

class Serializer implements SerializerInterface
{

    /**
     * @param array $data
     *
     * @return string
     */
    public function serialize(array $data): string
    {
        return json_encode($data);
    }

    /**
     * @param string $data
     *
     * @return array
     */
    public function deserialize(string $data): array
    {
        return json_decode($data);
    }

}
