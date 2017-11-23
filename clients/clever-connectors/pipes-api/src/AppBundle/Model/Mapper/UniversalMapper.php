<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Mapper;

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 23.11.17
 * Time: 11:08
 */

use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Enum\TypeEnum;
use CleverConnectors\AppBundle\Model\Mapper\Exception\MapperException;
use CleverConnectors\AppBundle\Model\MapTemplate\MapField;
use DateTime;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Throwable;

/**
 * Class UniversalMapper
 *
 * @package CleverConnectors\AppBundle\Model\Mapper
 */
class UniversalMapper implements MapperInterface
{

    /**
     * @param MapTemplate $template
     * @param ProcessDto  $dto
     *
     * @return ProcessDto
     */
    public function process(MapTemplate $template, ProcessDto $dto): ProcessDto
    {
        $inputData   = $this->decodeData($dto);
        $outputData  = $this->walkMapTemplate($template, $inputData);
        $encodedData = $this->encodeData($outputData);

        return $dto->setData($encodedData);
    }

    /**
     * ---------------------------------------- MAPPING -------------------------------------
     */

    /**
     * @param MapTemplate $template
     * @param array       $data
     *
     * @return array
     * @throws MapperException
     */
    private function walkMapTemplate(MapTemplate $template, array $data): array
    {
        $output = [];
        foreach ($template->getFields() as $field) {
            $output[$field->getKey()] = $this->getDataFromInputFields($field, $data);
        }

        return $output;
    }

    /**
     * @param MapField $field
     * @param array    $data
     *
     * @return mixed
     * @throws MapperException
     */
    private function getDataFromInputFields(MapField $field, array $data)
    {
        $output = '';

        foreach ($field->getItems() as $item) {
            if (!is_scalar($item)) {
                throw new MapperException(
                    sprintf('Item "%s" must be a string fo field "%s"', serialize($item), $field)
                );
            }

            $key = FieldKeyGenerator::parseKey($item);

            if (count($key) == 1) {
                $output .= $data[reset($key)] ?? '';
            } else {
                $output .= $this->getDataWithInnerKey($key, $data);
            }

        }

        return $this->reformatOutputData($field, $output);
    }

    /**
     * @param array $keys
     * @param array $data
     *
     * @return mixed
     * @throws MapperException
     */
    private function getDataWithInnerKey(array $keys, array $data)
    {
        $firstKey = array_shift($keys);

        if (count($keys) > 0 && array_key_exists($firstKey, $data)) {
            return $this->getDataWithInnerKey($keys, $data[$firstKey]);
        }

        if (array_key_exists($firstKey, $data) && is_scalar($data[$firstKey])) {
            return $data[$firstKey];
        }

        throw new MapperException(sprintf('Key "%s" not found in data!', $firstKey));
    }

    /**
     * -------------------------------- OUTPUT FORMATTING ---------------------------------------------
     */

    /**
     * @param MapField $field
     * @param mixed    $data
     *
     * @return mixed
     * @throws MapperException
     */
    protected function reformatOutputData(MapField $field, $data)
    {

        switch ($field->getType()) {
            case TypeEnum::TEXT:
                return $this->formatText($data);
            case TypeEnum::URL:
                return $this->formatUrl($data);
            case TypeEnum::DATE:
                return $this->formatDate($data);
            case TypeEnum::BOOL:
                return $this->formatBool($data);
            case TypeEnum::NUMBER:
                return $this->formatNumber($data);
            default:
                throw new MapperException(
                    sprintf('Type "%s" is not supported for field "%s"', $field->getType(), $field->getKey())
                );
        }
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    protected function formatText($data): string
    {
        return (string) $data;
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    protected function formatUrl($data): string
    {
        return (string) new Uri($data);
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    protected function formatDate($data): string
    {
        return (new DateTime($data))->format(DateTime::ISO8601);
    }

    /**
     * @param mixed $data
     *
     * @return bool
     */
    protected function formatBool($data): bool
    {
        return (bool) $data;
    }

    /**
     * @param mixed $data
     *
     * @return int|float
     */
    protected function formatNumber($data)
    {
        return floatval($data);
    }

    /**
     * -------------------------------- PARSING & DECODING -------------------------------------------
     */

    /**
     * @param ProcessDto $dto
     *
     * @return array
     * @throws MapperException
     */
    protected function decodeData(ProcessDto $dto): array
    {
        try {
            return json_decode($dto->getData(), TRUE);
        } catch (Throwable $e) {
            throw new MapperException($e->getMessage(), MapperException::PARSE_ERROR, $e);
        }
    }

    /**
     * @param array $data
     *
     * @return string
     */
    protected function encodeData(array $data): string
    {
        return json_encode($data);
    }

}