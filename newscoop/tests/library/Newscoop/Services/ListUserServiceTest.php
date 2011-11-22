<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Services;

use Newscoop\Entity\User,
    Newscoop\Entity\User\Group,
    Newscoop\Entity\Author;

class ListUserServiceTest extends \RepositoryTestCase
{
    /** Newscoop\Services\ListUserService */
    protected $service;

    public function setUp()
    {
        parent::setUp('Newscoop\Entity\User', 'Newscoop\Entity\UserAttribute', 'Newscoop\Entity\Acl\Role', 'Newscoop\Entity\User\Group', 'Newscoop\Entity\Author');
        $this->service = new ListUserService(array(
            'blog' => array(
                'role' => 1,
            ),
        ), $this->em);
    }

    public function testUser()
    {
        $this->assertInstanceOf('Newscoop\Services\ListUserService', $this->service);
    }

    public function testOrderByRank()
    {
        $user = new User('email');
        $this->em->persist($user);
        $this->em->flush();
        $user->addAttribute('points', 1);

        $user = new User('email');
        $this->em->persist($user);
        $this->em->flush();
        $user->addAttribute('points', 2);

        $this->em->flush();
        $this->em->clear();

        $this->assertEquals(2, $this->service->countBy());
    }

    public function testGetRandomList()
    {
        $this->addUser('1');
        $this->addUser('2');
        $this->addUser('3', 0, 0);
        $this->addUser('4', 0, 1);
        $this->addUser('5', 1, 0);
        $this->addUser('6');
        $this->addUser('7');

        $list1 = array_map(function($user) {
            return $user->getId();
        }, $this->service->getRandomList());

        $this->assertEquals(4, count($list1));

        $list2 = array_map(function($user) {
            return $user->getId();
        }, $this->service->getRandomList());

        $this->assertEquals(4, count($list2));

        $this->assertNotEquals($list1, $list2);
    }

    public function testGetEditors()
    {
        $blogRole = new Group();
        $blogRole->setName('blogger');

        $author1 = new Author('tic1', 'toc');
        $author2 = new Author('tic2', 'toc');

        $this->em->persist($blogRole);
        $this->em->persist($author1);
        $this->em->persist($author2);
        $this->em->flush();

        $user = new User();
        $user->setUsername('user')
            ->setEmail('user@example.com')
            ->setActive(true);

        $admin = new User();
        $admin->setUsername('admin')
            ->setEmail('admin@example.com')
            ->setActive(true)
            ->setAdmin(true);

        $editor = new User();
        $editor->setUsername('editor')
            ->setEmail('editor@example.com')
            ->setActive(true)
            ->setAdmin(true)
            ->setAuthor($author1);

        $blogger = new User();
        $blogger->setUsername('blogger')
            ->setEmail('blogger@example.com')
            ->setActive(true)
            ->setAdmin(true)
            ->setAuthor($author2)
            ->addUserType($blogRole);

        $this->em->persist($user);
        $this->em->persist($admin);
        $this->em->persist($editor);
        $this->em->persist($blogger);
        $this->em->flush();

        $service = new ListUserService(array('blog' => array(
            'role' => $blogRole->getId(),
        )), $this->em);

        $editors = $service->findEditors();
        $this->assertEquals(1, count($editors));
        $this->assertEquals($editor->getId(), $editors[0]->getId());
        $this->assertEquals(1, $service->getEditorsCount());
    }

    public function testFindByUsernameStartsWith()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder('Newscoop\Entity\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Newscoop\Entity\User'))
            ->will($this->returnValue($repository));

        $value = 'testval';
        $repository->expects($this->any())
            ->method('findByUsernameFirstCharacterIn')
            ->with($this->equalTo(array('b')), $this->equalTo(1), $this->equalTo(2))
            ->will($this->returnValue($value));

        $service = new ListUserService($GLOBALS['application']->getOptions(), $em);
        $this->assertEquals($value, $service->findByUsernameFirstCharacter('b', 1, 2));
        $this->assertEquals($value, $service->findByUsernameFirstCharacter('B', 1, 2));
    }

    public function testCountByUsernameFirstCharacter()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder('Newscoop\Entity\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $repository->expects($this->once())
            ->method('countByUsernameFirstCharacterIn')
            ->will($this->returnValue(1));

        $service = new ListUserService($GLOBALS['application']->getOptions(), $em);
        $this->assertEquals(1, $service->countByUsernameFirstCharacter('b'));
    }

    public function testFindByUsernameStartsWithCharacterGroups()
    {
        $groups = array(
            'a' => array('a', 'ä', 'à', 'â', 'æ'),
            'c' => array('c', 'ç'),
            'e' => array('e', 'è', 'é', 'ê', 'ë'),
            'i' => array('i', 'î', 'ï', 'ì', 'í'),
            'o' => array('o', 'ö', 'ô', 'œ', 'ò', 'ó'),
            's' => array('s', 'ß'),
            'u' => array('u', 'ü', 'ù', 'û', 'ú'),
            'y' => array('y', 'ÿ'),
        );

        foreach (range('a', 'z') as $character) {
            $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                ->disableOriginalConstructor()
                ->getMock();

            $repository = $this->getMockBuilder('Newscoop\Entity\Repository\UserRepository')
                ->disableOriginalConstructor()
                ->getMock();

            $em->expects($this->once())
                ->method('getRepository')
                ->will($this->returnValue($repository));

            $param = isset($groups[$character]) ?
                $groups[$character] : array($character);

            $repository->expects($this->any())
                ->method('findByUsernameFirstCharacterIn')
                ->with($this->equalTo($param), $this->anything(), $this->anything());

            $service = new ListUserService($GLOBALS['application']->getOptions(), $em);
            $service->findByUsernameFirstCharacter($character);
        }
    }

    public function testDbHandlingLower()
    {
        $connection = $this->em->getConnection();
        if ($connection->getDriver()->getDatabasePlatform()->getName() == 'sqlite') {
            $this->markTestSkipped('Not working with sqlite db');
            return;
        }

        $this->assertEquals('ä', $connection->fetchColumn("SELECT LOWER('Ä')"));
    }

    private function addUser($name, $status = 1, $isPublic = 1)
    {
        $user = new User($name);
        $user->setStatus($status);
        $user->setPublic($isPublic);
        $this->em->persist($user);
        $this->em->flush();
    }
}
