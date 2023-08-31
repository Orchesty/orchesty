<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyInstaller;

use Symfony\Component\Finder\Finder;

/**
 * Class TplgLoader
 *
 * @package Hanaboso\PipesFramework\TopologyInstaller
 */
final class TplgLoader
{

    public const TPLG = '.tplg';

    /**
     * @param string $dir
     *
     * @return mixed[]
     */
    public function load(string $dir): array
    {
        $files  = [];
        $finder = new Finder();
        $finder->name(sprintf('*%s', self::TPLG))->in($dir);

        foreach ($finder as $file) {
            $key         = self::getName($file->getFilename());
            $files[$key] = $file;
        }
        unset($finder);
        ksort($files);

        return $files;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function getName(string $name): string
    {
        return str_replace(self::TPLG, '', $name);
    }

}
