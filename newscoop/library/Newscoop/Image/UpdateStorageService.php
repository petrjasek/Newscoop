<?php
/**
 * @package Newscoop
 * @copyright 2012 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Image;

use Doctrine\ORM\EntityManager;
use Newscoop\Storage\StorageService;

/**
 * Upload Storage Service
 */
class UpdateStorageService
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Newscoop\Storage\StorageService
     */
    private $storage;

    /**
     * @param Doctrine\ORM\EntityManager $em
     * @param Newscoop\Storage\StorageService $storage
     */
    public function __construct(EntityManager $em, StorageService $storage)
    {
        $this->em = $em;
        $this->storage = $storage;
    }

    /**
     * Update storage for given number of images
     *
     * @param int $batchSize
     * @return void
     */
    public function updateStorage($batchSize = 100)
    {
        $images = $this->getImageRepository()
            ->findImagesForStorageUpdate($batchSize);

        foreach ($images as $image) {
            $this->updateImage($image);
        }

        $this->em->flush();
    }

    /**
     * Update single image storage
     *
     * @param Newscoop\Image\LocalImage $image
     * @return void
     */
    private function updateImage(LocalImage $image)
    {
        $image->updateStorage(
            $this->storage->moveImage($image->getPath()),
            $this->storage->moveThumbnail($image->getThumbnailPath())
        );
    }

    /**
     * Test if given image can be deleted
     *
     * @param string $imagePath
     * @return bool
     */
    public function isDeletable($imagePath)
    {
        return $this->getImageRepository()
            ->getImageFileReferencesCount($imagePath) <= 1;
    }

    /**
     * Get image repository
     *
     * @return Newscoop\Entity\Repository\ImageRepository
     */
    private function getImageRepository()
    {
        return $this->em->getRepository('Newscoop\Image\LocalImage');
    }
}
