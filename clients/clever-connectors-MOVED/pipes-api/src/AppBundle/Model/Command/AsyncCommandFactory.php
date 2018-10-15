<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10.10.17
 * Time: 14:50
 */

namespace CleverConnectors\AppBundle\Model\Command;

use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use RuntimeException;

/**
 * Class CommandFactory
 *
 * @package AppBundle\Model\Command
 */
class AsyncCommandFactory
{

    /**
     * @var string
     */
    private $projectDir;

    /**
     * CommandFactory constructor.
     *
     * @param string $projectDir
     */
    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * @param LoopInterface $loop
     * @param string        $command
     *
     * @return Promise
     */
    public function create(LoopInterface $loop, string $command): Promise
    {
        $process = new Process(sprintf('bin/console %s', $command), $this->projectDir);
        $process->start($loop);

        return new Promise(function ($resolve, $reject) use ($process): void {

            $buffer = '';
            $process->stdout->on('data', function (string $chunk) use (&$buffer): void {
                $buffer .= $chunk;
            });

            $process->on('exit', function ($exitCode) use ($resolve, $reject, &$buffer): void {
                if ($exitCode === 0) {
                    $resolve(trim($buffer));
                } else {
                    $reject(new RuntimeException(sprintf('Process exited with code %s.', $exitCode)));
                }
            });

        });
    }

}