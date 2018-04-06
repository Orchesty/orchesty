<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Exception;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class Comparator
 *
 * @package CleverConnectors\AppBundle\Model\CustomNode
 */
final class Comparator implements CustomNodeInterface
{

    private const KEY_SOURCE           = 'src';
    private const KEY_DESTINATION      = 'dst';
    private const KEY_SETTINGS         = 'settings';
    private const KEY_SETTINGS_ID      = 'id_key';
    private const KEY_SETTINGS_COMPARE = 'compare_key';

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = $this->validateData($dto);

        try {
            $data = $this->prepareData($data);
        } catch (Exception $e) {
            throw new CleverConnectorsException(
                'Preparation of data for compare failed. ' . $e->getMessage(),
                CleverConnectorsException::INVALID_DATA,
                $e
            );
        }

        try {
            $out = $this->compare(
                $data[self::KEY_SOURCE],
                $data[self::KEY_DESTINATION],
                $data[self::KEY_SETTINGS]
            );
        } catch (Exception $e) {
            throw new CleverConnectorsException(
                'Comparing failed. ' . $e->getMessage(),
                CleverConnectorsException::INVALID_DATA,
                $e
            );
        }

        $dto->setData(json_encode($out));

        return $dto;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function prepareData(array $data): array
    {
        $key = $this->getIdKey($data[self::KEY_SETTINGS]);
        if ($key !== NULL) {
            $tmp = [];
            foreach ($data[self::KEY_SOURCE] as $srcItem) {
                $tmp[] = $srcItem[$key];
            }

            $data[self::KEY_SOURCE] = $tmp;
        }

        $compareKey = $this->getCompareKey($data[self::KEY_SETTINGS]);
        if ($compareKey !== NULL) {
            $tmp = [];
            foreach ($data[self::KEY_DESTINATION] as $dstItem) {
                $tmp[] = $dstItem[$compareKey];
            }

            $data[self::KEY_DESTINATION] = $tmp;
        }

        return $data;
    }

    /**
     * @param array $src
     * @param array $dist
     * @param array $settings
     *
     * @return array
     */
    private function compare(array $src, array $dist, array $settings = []): array
    {
        return [
            'create' => array_values(array_diff($src, $dist)),
            'delete' => array_values(array_diff($dist, $src)),
            'update' => [], // TODO - howto?
        ];
    }

    /**
     * @param ProcessDto $dto
     *
     * @return array
     * @throws CleverConnectorsException
     */
    private function validateData(ProcessDto $dto): array
    {
        $data = json_decode($dto->getData(), TRUE);

        if (!is_array($data)) {
            throw new CleverConnectorsException('Invalid data given.', CleverConnectorsException::INVALID_DATA);
        }

        if (!array_key_exists(self::KEY_SOURCE, $data) ||
            !array_key_exists(self::KEY_DESTINATION, $data)) {
            throw new CleverConnectorsException(
                sprintf('Missing mandatory "%s" or "%s" fields in given data', self::KEY_SOURCE, self::KEY_DESTINATION),
                CleverConnectorsException::INVALID_DATA
            );
        }

        if ($this->hasSettings($data)) {
            $this->validateSettings($data[self::KEY_SETTINGS]);
        } else {
            $data[self::KEY_SETTINGS] = [];
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    private function hasSettings(array $data): bool
    {
        return array_key_exists(self::KEY_SETTINGS, $data) && !empty($data[self::KEY_SETTINGS]);
    }

    /**
     * @param array $settings
     *
     * @throws CleverConnectorsException
     */
    private function validateSettings(array $settings)
    {
        if ($this->getCompareKey($settings) !== NULL &&
            $this->getIdKey($settings) === NULL
        ) {
            throw new CleverConnectorsException(
                sprintf(
                    'Settings field contains "%s" but does not contain "%s"',
                    self::KEY_SETTINGS_COMPARE,
                    self::KEY_SETTINGS_ID
                ),
                CleverConnectorsException::INVALID_DATA
            );
        }
    }

    /**
     * @param array $settings
     *
     * @return null|string
     */
    private function getIdKey(array $settings): ?string
    {
        if (array_key_exists(self::KEY_SETTINGS_ID, $settings)) {
            return $settings[self::KEY_SETTINGS_ID];
        }

        return NULL;
    }

    /**
     * @param array $settings
     *
     * @return null|string
     */
    private function getCompareKey(array $settings): ?string
    {
        if (array_key_exists(self::KEY_SETTINGS_COMPARE, $settings)) {
            return $settings[self::KEY_SETTINGS_COMPARE];
        }

        return NULL;
    }
}
