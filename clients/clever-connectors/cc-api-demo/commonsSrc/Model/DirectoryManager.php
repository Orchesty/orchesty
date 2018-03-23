<?php declare(strict_types=1);

namespace CleverCore\Commons\Model;

use CleverCore\Commons\Entities\Directory;
use CleverCore\Commons\Enums\DirectorySourceEnum;
use CleverCore\Commons\Exceptions\DirectoryException;
use CleverCore\Commons\Repositories\DirectoryRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;

/**
 * Class DirectoryManager
 *
 * @package CleverCore\Commons\Model\Directory
 */
class DirectoryManager
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ObjectRepository|DirectoryRepository
     */
    private $directoryRepository;

    /**
     * DirectoryManager constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em                  = $em;
        $this->directoryRepository = $em->getRepository(Directory::class);
    }

    /**
     * @param DirectoryDto $dto
     *
     * @return Directory
     * @throws ORMException
     */
    public function createDirectory(DirectoryDto $dto): Directory
    {
        $directory = new Directory($dto->getLabel());

        if ($dto->getParent()) {
            $directory->setParent($dto->getParent());
        }

        $directory
            ->setSource($dto->getSource())
            ->setClientId($dto->getClientId())
            ->setDescription($dto->getDescription());

        $this->em->persist($directory);
        $this->em->flush();

        return $directory;
    }

    /**
     * @param Directory   $directory
     * @param null|string $label
     * @param null|string $description
     *
     * @return Directory
     * @throws ORMException
     */
    public function updateDirectory(Directory $directory, ?string $label, ?string $description): Directory
    {
        if ($label) {
            $directory->setLabel($label);
        }

        if ($description) {
            $directory->setDescription($description);
        }

        $this->em->flush();

        return $directory;
    }

    /**
     * @param Directory      $directory
     * @param Directory|null $parent
     *
     * @return Directory
     * @throws ORMException
     */
    public function move(Directory $directory, ?Directory $parent = NULL): Directory
    {
        $directory->setParent($parent);

        $this->em->flush();

        return $directory;
    }

    /**
     * @param Directory $directory
     *
     * @return Directory[]
     */
    public function deleteDirectory(Directory $directory): array
    {
        $list = [];
        $this->addChildren($directory, $list);

        return $list;
    }

    /**
     * @param Directory[] $children
     *
     * @throws ORMException
     */
    public function deleteChildren(array $children): void
    {
        foreach ($children as $child) {
            $this->em->remove($child);
        }
        $this->em->flush();
    }

    /**
     * @param Directory[] $children
     *
     * @throws ORMException
     */
    public function moveChildren(array $children): void
    {
        $root = array_shift($children);
        foreach ($children as $child) {
            $child->setParent($root->getParent());
        }
        $this->em->flush(); // Required - otherwise remove will delete even all moved children
        $this->em->remove($root);
        $this->em->flush();
    }

    /**
     * @param string $clientId
     * @param string $source
     *
     * @return array|mixed
     * @throws DirectoryException
     */
    public function getSource(string $clientId, string $source)
    {
        $node = $this->directoryRepository->findOneBy([
            'parent'   => NULL,
            'clientId' => $clientId,
            'source'   => DirectorySourceEnum::isValid($source),
        ]);

        if (!$node) {
            throw new DirectoryException(
                sprintf('Source [client ID: %s, source: %s] not found.', $clientId, $source),
                DirectoryException::DIRECTORY_NOT_FOUND
            );
        }

        $children = $this->directoryRepository->getChildren($node, FALSE, NULL, 'ASC', TRUE);

        return $children;
    }

    /**
     * @param string   $clientId
     * @param string   $source
     * @param string   $label
     * @param int|null $lvl
     *
     * @return array|mixed
     * @throws DirectoryException
     */
    public function getChildren(string $clientId, string $source, string $label, ?int $lvl = NULL)
    {
        $criteria = [
            'label'    => $label,
            'clientId' => $clientId,
            'source'   => DirectorySourceEnum::isValid($source),
        ];

        if ($lvl) {
            $criteria['lvl'] = $lvl;
        }

        $node = $this->directoryRepository->findOneBy($criteria);

        if (!$node) {
            $message = sprintf('Source [client ID: %s, source: %s, label: %s] not found.', $clientId, $source, $label);
            if ($lvl) {
                $message = sprintf(
                    'Source [client ID: %s, source: %s, label: %s, lvl: %s] not found.',
                    $clientId, $source, $label, $lvl
                );
            }

            throw new DirectoryException($message, DirectoryException::DIRECTORY_NOT_FOUND);
        }

        return $this->directoryRepository->getChildren($node);
    }

    /**
     * @param Directory $directory
     * @param array     $list
     */
    private function addChildren(Directory $directory, array &$list): void
    {
        $list[] = $directory;
        foreach ($directory->getChildren() as $child) {
            $this->addChildren($child, $list);
        }
    }

}