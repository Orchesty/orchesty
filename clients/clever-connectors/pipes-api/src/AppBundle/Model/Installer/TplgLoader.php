<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 8.11.17
 * Time: 13:26
 */

namespace CleverConnectors\AppBundle\Model\Installer;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class TplgLoader
 *
 * @package CleverConnectors\AppBundle\Model\Installer
 */
class TplgLoader
{

    public const TPLG = '.tplg';

    /**
     * @param SplFileInfo $file
     *
     * @return string
     */
    public static function getName(SplFileInfo $file): string
    {
        return str_replace(self::TPLG, '', $file->getFilename());
    }

    /**
     * @param string $dir
     *
     * @return array
     */
    public function load(string $dir): array
    {
        $files  = [];
        $finder = $this->createFinder();
        $finder->name(sprintf('*%s', self::TPLG))->in($dir);

        foreach ($finder as $file) {
            $key         = self::getName($file);
            $files[$key] = $file;
        }
        unset($finder);
        ksort($files);

        return $files;
    }

    /**
     * -------------------------------- HELPERS ---------------------------------
     */

    /**
     * @return Finder
     */
    protected function createFinder(): Finder
    {
        return new Finder();
    }

}