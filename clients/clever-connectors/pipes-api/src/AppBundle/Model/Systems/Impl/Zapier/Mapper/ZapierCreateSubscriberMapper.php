<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 1/17/18
 * Time: 2:27 PM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class ZapierCreateSubscriberMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Mapper
 */
class ZapierCreateSubscriberMapper implements CustomNodeInterface
{

    /**
     * @var ObjectRepository|SystemInstallRepository
     */
    protected $systemInstallRepository;

    /**
     * ZapierCreateSubscriberMapper constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());

        $fields = [];

        $data = json_decode($dto->getData(), TRUE);
        if (array_key_exists(CleverFieldsEnum::EMAIL, $data)) {
            $fields['email'] = $data[CleverFieldsEnum::EMAIL];
        }
        if (array_key_exists(CleverFieldsEnum::FIRST_NAME, $data)) {
            $fields['first_name'] = $data[CleverFieldsEnum::FIRST_NAME];
        }
        if (array_key_exists(CleverFieldsEnum::LAST_NAME, $data)) {
            $fields['last_name'] = $data[CleverFieldsEnum::LAST_NAME];
        }
        if (array_key_exists(CleverFieldsEnum::FOREIGN_ID, $data)) {
            $fields['id'] = $data[CleverFieldsEnum::FOREIGN_ID];
        }

        $fields[CleverCustomKeysEnum::UNSUBSCRIBE] = FALSE;
        $fields[CleverCustomKeysEnum::HARD_BOUNCE] = FALSE;

        return $dto->setData(json_encode($fields));
    }

    /**
     * @param string        $key
     * @param SystemInstall $systemInstall
     *
     * @return string
     */
    private function getHash(string $key, SystemInstall $systemInstall): string
    {
        $hash = $systemInstall->getSettings()[$key] ?? '';

        return $hash;
    }

}