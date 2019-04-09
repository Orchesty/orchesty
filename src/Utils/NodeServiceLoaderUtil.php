<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Utils;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 *
 */
class NodeServiceLoaderUtil
{

    /**
     * @param array  $dirs
     * @param string $nodeType
     * @param array  $exclude
     *
     * @return array
     */
    public static function getServices(array $dirs, string $nodeType, array $exclude = []): array
    {
        $finder = new Finder();
        $res    = [];

        foreach ($dirs as $dir) {
            $finder->name(['*.yaml', '*.yml'])->in($dir);

            foreach ($finder as $file) {
                $list = Yaml::parse((string) $file->getContents());

                foreach (array_keys($list['services']) as $key) {
                    if (strrpos((string) $key, $nodeType) !== 0) {
                        continue;
                    }

                    $shortened = str_replace( sprintf('%s.', $nodeType ), '', (string) $key);
                    if (in_array($shortened, $exclude)) {
                        unset($exclude[$shortened]);
                        continue;
                    }
                    if (in_array($shortened, $res)) {
                        continue;
                    }
                    $res[] = $shortened;
                }
            }
        }

        return $res;

    }

}