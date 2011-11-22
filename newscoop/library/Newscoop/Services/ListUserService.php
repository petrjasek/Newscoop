<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Services;

use Doctrine\ORM\EntityManager,
    Newscoop\Entity\User;

/**
 * List User service
 */
class ListUserService
{
    /** @var Doctrine\ORM\EntityManager */
    protected $em;

    /** @var array */
    private $config = array();

    /** @var Newscoop\Entity\Repository\UserRepository */
    protected $repository;

    /**
     * @param array $config
     * @param Doctrine\ORM\EntityManager $em
     */
    public function __construct(array $config, EntityManager $em)
    {
        $this->config = $config;
        $this->em = $em;
        $this->repository = $this->em->getRepository('Newscoop\Entity\User');
    }

    /**
     * Find by given criteria
     *
     * @param array $criteria
     * @param array $orderBy
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findBy(array $criteria, array $orderBy = array(), $limit = NULL, $offset = NULL)
    {
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Count by given criteria
     *
     * @param array $criteria
     * @return int
     */
    public function countBy(array $criteria = array())
    {
        return $this->repository->countBy($criteria);
    }

    /**
     * Find one user by criteria
     *
     * @param array $criteria
     * @return Newscoop\Entity\User
     */
    public function findOneBy(array $criteria)
    {
        return $this->repository->findOneBy($criteria);
    }

    /**
     * List active users
     *
     * @return array
     */
    public function getActiveUsers($countOnly=false, $page=1, $limit=8)
    {
        $offset = ($page-1) * $limit;

        $result = $this->repository->findActiveUsers($countOnly, $offset, $limit);

        if($countOnly) {
            return $result[1];
        }

        return $result;
    }

    /**
     * Get random list of users
     *
     * @param int $limit
     * @return array
     */
    public function getRandomList($limit = 25)
    {
        return $this->repository->getRandomList($limit);
    }

    /**
     * List users by first letter
     *
     * @return array
     */
    public function findUsersLastNameInRange($letters, $countOnly=false, $page=1, $limit=25)
    {
        $offset = ($page-1) * $limit;

        $result = $this->repository->findUsersLastNameInRange($letters, $countOnly, $offset, $limit);

        if($countOnly) {
            return $result[1];
        }

        return $result;
    }

    /**
     * Find user by string
     *
     * @return array
     */
    public function findUsersBySearch($search, $countOnly=false, $page=1, $limit=25)
    {
        $offset = ($page-1) * $limit;

        $result = $this->repository->searchUsers($search, $countOnly, $offset, $limit);

        if($countOnly) {
            return $result[1];
        }

        return $result;
    }

    /**
     * List editors
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findEditors($limit = NULL, $offset = NULL)
    {
        return $this->repository->findEditors($this->config['blog']['role'], $limit, $offset);
    }

    /**
     * Get editors count
     *
     * @return int
     */
    public function getEditorsCount()
    {
        return $this->repository->getEditorsCount($this->config['blog']['role']);
    }

    /**
     * Find users by first character of username
     *
     * @param string $character
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findByUsernameFirstCharacter($character, $limit = 25, $offset = 0)
    {
        return $this->repository->findByUsernameFirstCharacterIn($this->getCharacters($character), $limit, $offset);
    }

    /**
     * Count users by first character of username
     *
     * @param string $character
     * @return int
     */
    public function countByUsernameFirstCharacter($character)
    {
        return $this->repository->countByUsernameFirstCharacterIn($this->getCharacters($character));
    }

    /**
     * Get characters for given character group
     *
     * @param string $character
     * @return array
     */
    private function getCharacters($character)
    {
        $character = strtolower($character);
        return isset($this->config['characterGroup'][$character]) ?
            explode(' ', $this->config['characterGroup'][$character]) : array($character);
    }
}
