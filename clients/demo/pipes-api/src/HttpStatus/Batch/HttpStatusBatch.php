<?php declare(strict_types=1);

namespace Demo\HttpStatus\Batch;

use Demo\HttpStatus\HttpStatusApplication;
use Hanaboso\CommonsBundle\Process\BatchProcessDto;
use Hanaboso\PipesPhpSdk\Batch\BatchAbstract;
use Hanaboso\Utils\String\Json;

/**
 * Class HttpStatusBatch
 *
 * @package Demo\HttpStatus\Batch
 */
final class HttpStatusBatch extends BatchAbstract
{

    /**
     * @return string
     */
    public function getName(): string
    {
        return sprintf('%s-batch', HttpStatusApplication::NAME);
    }

    /**
     * @param BatchProcessDto $dto
     *
     * @return BatchProcessDto
     */
    public function processAction(BatchProcessDto $dto): BatchProcessDto
    {
        for ($i = 0; $i < ($dto->getJsonData()['size'] ?? 100); $i++) {
            $dto->addItem(Json::encode(['counter' => $i]));
        }

        return $dto;
    }

}
