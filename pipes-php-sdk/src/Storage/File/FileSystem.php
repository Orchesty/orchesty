<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Storage\File;

use Error;
use Exception;
use Hanaboso\PipesPhpSdk\Storage\DataStorage\Document\DataStorageDocument;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Traits\LoggerTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class FileSystem
 *
 * @package Hanaboso\PipesPhpSdk\Storage\File
 */
final class FileSystem implements LoggerAwareInterface
{

    use LoggerTrait;

    /**
     * @var string[]
     */
    private array $lockedFiles = [];

    /**
     * FileSystem constructor.
     *
     * @param int $millisecondsDelayOnFail
     * @param int $maxTries
     */
    public function __construct(
        private readonly int $millisecondsDelayOnFail = 2_000,
        private readonly int $maxTries                = 5,
    )
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param string                $file
     * @param DataStorageDocument[] $data
     * @param int                   $actualTry
     *
     * @return bool
     * @throws Exception
     */
    public function write(string $file, array $data, int $actualTry = 1): bool {
        if ($actualTry > $this->maxTries) {
            throw new Exception(sprintf('Max tries haw been reached. Cannot write to file [%s]', $file));
        }

        if ($this->lockedFiles[$file] ?? FALSE) {
            usleep($this->millisecondsDelayOnFail);

            return $this->write($file, $data,$actualTry +1);
        }

        $this->lock($file);

        $error = NULL;
        try {
            $tmpPath = $this->getDirectoryPath(TRUE);
            if (!file_exists($tmpPath)) {
                mkdir($tmpPath,0777, TRUE);
            }

            $dataPath = $this->getDirectoryPath();
            if (!file_exists($dataPath)) {
                mkdir($dataPath,0777, TRUE);
            }

            $tmpFile  = $this->getFilePath($file, TRUE);
            $openFile = fopen($tmpFile, 'w');
            if (!$openFile) {
                usleep($this->millisecondsDelayOnFail);

                return $this->write($file, $data,$actualTry +1);
            }

            fwrite(
                $openFile,
                mb_convert_encoding(Json::encode(array_map(static fn($item) => $item->toArray(), $data)), 'UTF-8'),
            );
            fclose($openFile);

            rename($tmpFile, $this->getFilePath($file));
        } catch (Error $e) {
            $error = $e;
            $this->logger->error(
                sprintf('Write to file [%s] was not successful. Tries [%s/%s]', $file, $actualTry, $this->maxTries),
            );
        } finally {
            $this->unlock($file);
        }

        return !$error;
    }

    /**
     * @param string $file
     * @param int    $actualTry
     *
     * @return DataStorageDocument[]
     * @throws Exception
     */
    public function read(string $file, int $actualTry = 1): array {
        try {
            $dataPath = $this->getFilePath($file);
            if (!file_exists($dataPath)) {
                return [];
            }

            if ($actualTry > $this->maxTries) {
                throw new Exception(sprintf('Max tries haw been reached. Cannot read to file [%s]', $file));
            }

            if ($this->lockedFiles[$file] ?? FALSE) {
                usleep($this->millisecondsDelayOnFail);

                return $this->read($file, $actualTry +1);
            }

            $openFile = fopen($dataPath, 'r');
            if (!$openFile) {
                usleep($this->millisecondsDelayOnFail);

                return $this->read($file, $actualTry +1);
            }

            $data = Json::decode(fread($openFile, filesize($dataPath) ?: 1) ?: '');
            fclose($openFile);

            return array_map(static fn($item) => DataStorageDocument::fromJson($item), $data);
        } catch (error) {
            $this->logger->error(
                sprintf('Read file [%s] was not successful. Tries [%s/%s]', $file, $actualTry, $this->maxTries),
            );
            usleep($this->millisecondsDelayOnFail);

            return $this->read($file, $actualTry +1);
        }
    }

    /**
     * @param string $file
     * @param int    $actualTry
     *
     * @return bool
     * @throws Exception
     */
    public function delete(string $file, int $actualTry = 1): bool {
        try {
            $dataPath = $this->getFilePath($file);
            if (!file_exists($dataPath)) {
                return TRUE;
            }

            if ($actualTry > $this->maxTries) {
                throw new Exception(sprintf('Max tries haw been reached. Cannot delete to file [%s]', $file));
            }
            unlink($dataPath);

            return TRUE;
        } catch (error) {
            $this->logger->error(
                sprintf('Delete file [%s] was not successful. Tries [%s/%s]', $file, $actualTry, $this->maxTries),
            );

            usleep($this->millisecondsDelayOnFail);

            return $this->delete($file, $actualTry +1);
        }
    }

    /**
     * @param bool $tmp
     *
     * @return string
     */
    public function getDirectoryPath(bool $tmp = FALSE): string {
        return sprintf('/tmp/orchesty/%s',$tmp ? 'tmp': 'data');
    }

    /**
     * @param string $file
     * @param bool   $tmp
     *
     * @return string
     */
    public function getFilePath(string $file, bool $tmp = FALSE): string {
        return sprintf('%s/%s.json', $this->getDirectoryPath($tmp), $file);
    }

    /**
     * @param string $file
     *
     * @return void
     */
    private function lock(string $file): void {
        $this->lockedFiles[$file] = $file;
    }

    /**
     * @param string $file
     *
     * @return void
     */
    private function unlock(string $file): void {
        $this->lockedFiles = array_filter($this->lockedFiles, static fn($key) => $key !== $file);
    }

}
