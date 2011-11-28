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

class UserServiceTest extends \RepositoryTestCase
{
    /** @var Newscoop\Services\UserService */
    protected $service;

    /** @var Zend_Auth */
    protected $auth;

    /** @var Doctrine\ORM\EntityManager */
    protected $em;

    /** @var Newscoop\Entity\Repository\UserRepository */
    protected $repository;

    /** @var Newscoop\Entity\User */
    private $user;

    public function setUp()
    {
        parent::setUp('Newscoop\Entity\User', 'Newscoop\Entity\Acl\Role', 'Newscoop\Entity\UserAttribute', 'Newscoop\Entity\User\Group', 'Newscoop\Entity\Author');

        $this->auth = $this->getMockBuilder('Zend_Auth')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->em->getRepository('Newscoop\Entity\User');

        $this->service = new UserService(array(
            'blog' => array(
                'role' => 1,
            )), $this->em, $this->auth);

        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setUsername('test');
        $this->user->setFirstName('Foo');
        $this->user->setLastName('Bar');
    }

    public function testUser()
    {
        $this->assertInstanceOf('Newscoop\Services\UserService', $this->service);
    }

    public function testGetCurrentUser()
    {
        $this->em->persist($this->user);
        $this->em->flush();

        $this->auth->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(true));

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue(1));

        $this->assertEquals($this->user, $this->service->getCurrentUser());
    }

    public function testGetCurrentUserNotAuthorized()
    {
        $this->auth->expects($this->once())
            ->method('hasIdentity')
            ->will($this->returnValue(false));

        $this->assertNull($this->service->getCurrentUser());
    }

    public function testFind()
    {
        $this->em->persist($this->user);
        $this->em->flush();

        $this->assertEquals($this->user, $this->service->find(1));
    }

    public function testSaveNew()
    {
        $userdata = array(
            'username' => 'foobar',
            'first_name' => 'foo',
            'last_name' => 'bar',
            'email' => 'foo@bar.com',
        );

        $user = $this->service->save($userdata);
        $this->assertInstanceOf('Newscoop\Entity\User', $user);
        $this->assertEquals(1, $user->getId());
    }

    public function testDeleteActive()
    {
        $this->user->setActive(true);
        $this->em->persist($this->user);
        $this->em->flush();

        $this->user->addAttribute('tic', 'toc');
        $this->em->persist($this->user);
        $this->em->flush();

        $this->assertTrue($this->user->isActive());

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue(3));

        sleep(2); // for testing difference in create/update time
        $this->service->delete($this->user);

        $this->assertFalse($this->user->isActive());
        $this->assertFalse($this->user->isPublic());
        $this->assertFalse($this->user->isAdmin());

        $this->assertEmpty($this->user->getEmail());
        $this->assertEmpty($this->user->getFirstName());
        $this->assertEmpty($this->user->getLastName());
        $this->assertEmpty($this->user->getAttribute('tic'));
        $this->assertEmpty($this->user->getAttributes());
        $this->assertGreaterThan($this->user->getCreated()->getTimestamp(), $this->user->getUpdated()->getTimestamp());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testDeleteHimself()
    {
        $user = new User();
        $property = new \ReflectionProperty($user, 'id');
        $property->setAccessible(TRUE);
        $property->setValue($user, 1);

        $this->auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue(1));

        $this->service->delete($user);
    }

    public function testDeletePending()
    {
        $user = $this->service->createPending('test@example.com');

        $this->service->delete($user);

        $this->assertEmpty($this->service->find($user->getId()));
    }

    public function testGenerateUsername()
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('Foo Bar');
        $this->em->persist($user);
        $this->em->flush();

        $this->assertEquals('Foos Bar', $this->service->generateUsername('Foos', 'Bar'));
        $this->assertEquals('Foo Bar1', $this->service->generateUsername('Foo', 'Bar'));
        $this->assertEquals('', $this->service->generateUsername(' ', ' '));
        $this->assertEquals('Foo', $this->service->generateUsername('Foo', ''));
        $this->assertEquals('Bar', $this->service->generateUsername('', 'Bar'));
        $this->assertEquals('', $this->service->generateUsername('!@#$%^&*()+-={}[]\\|;\':"§-?/.>,<', ''));
        $this->assertEquals('_', $this->service->generateUsername('_', ''));
        $this->assertEquals('Foo Bar Jr', $this->service->generateUsername('Foo  Bar ', ' Jr '));
    }

    public function testSetActive()
    {
        $this->assertFalse($this->user->isActive());

        $this->service->setActive($this->user);

        $this->assertTrue($this->user->isActive());
    }

    public function testSave()
    {
        $data = array(
            'email' => 'info@example.com',
        );

        $this->assertEquals($this->user, $this->service->save($data, $this->user));
        $this->assertGreaterThan(0, $this->user->getId());
        $this->assertEquals('info@example.com', $this->user->getEmail());
    }

    public function testCreatePending()
    {
        $user = $this->service->createPending('email@example.com');
        $this->assertInstanceOf('Newscoop\Entity\User', $user);
        $this->assertTrue($user->isPublic());
        $this->assertGreaterThan(0, $user->getId());

        $next = $this->service->createPending('email@example.com');
        $this->assertEquals($user->getId(), $next->getId());
    }

    public function testSavePending()
    {
        $user = $this->service->createPending('info@example.com');
        $this->service->savePending(array('username' => 'test'), $user);

        $this->assertTrue($user->isActive());
        $this->assertTrue($user->isPublic());
    }

    /**
     * @expectedException InvalidArgumentException username
     */
    public function testUsernameCaseSensitivity()
    {
        $this->service->save(array(
            'email' => 'one@example.com',
            'username' => 'Foo Bar',
        ));

        $this->service->save(array(
            'email' => 'two@example.com',
            'username' => 'foo bar',
        ));
    }

    public function testCountPublicUsers()
    {
        $this->assertEquals(0, $this->service->countPublicUsers());

        $this->user->setActive();
        $this->em->persist($this->user);
        $this->em->flush();

        $this->assertEquals(0, $this->service->countPublicUsers());

        $this->user->setPublic();
        $this->em->flush();

        $this->assertEquals(1, $this->service->countPublicUsers());
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
        $this->addUser('8');

        $list1 = array_map(function($user) {
            return $user->getId();
        }, $this->service->getRandomList());

        $this->assertEquals(5, count($list1));

        $list2 = array_map(function($user) {
            return $user->getId();
        }, $this->service->getRandomList());

        $this->assertEquals(5, count($list2));

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

        $service = new UserService(array('blog' => array(
            'role' => $blogRole->getId(),
        )), $this->em, $this->auth);

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

        $service = new UserService($GLOBALS['application']->getOptions(), $em, $this->auth);
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

        $service = new UserService($GLOBALS['application']->getOptions(), $em, $this->auth);
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

            $service = new UserService($GLOBALS['application']->getOptions(), $em, $this->auth);
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

    public function testGetActiveUsers()
    {
        $this->addUser('active1');
        $this->addUser('active2');
        $this->addUser('nonpublic1', User::STATUS_ACTIVE, false);
        $this->addUser('inactive1', User::STATUS_INACTIVE, true);
        $this->addUser('inactive2', User::STATUS_DELETED, true);

        $author = new Author('author1', 'last');
        $this->em->persist($author);
        $this->em->flush();

        $user = $this->addUser('author1', User::STATUS_ACTIVE, true);
        $user->setAuthor($author);
        $this->em->flush();

        $blogRole = new Group();
        $blogRole->setName('blogger');
        $this->em->persist($blogRole);

        $author = new Author('author2', 'last');
        $this->em->persist($author);

        $this->em->flush();

        $user = $this->addUser('blogger1', User::STATUS_ACTIVE, true);
        $user->setAuthor($author);
        $user->addUserType($blogRole);
        $this->em->flush();

        $users = $this->service->getActiveUsers();
        $this->assertEquals(3, count($users));
        $this->assertEquals(3, $this->service->getActiveUsers(true));
    }

    public function testFindUsersBySearch()
    {
        $users = $this->service->findUsersBySearch('test');
    }

    /**
     * Add user to repository
     *
     * @param string $name
     * @param int $status
     * @param bool $isPublic
     * @return Newscoop\Entity\User
     */
    private function addUser($name, $status = User::STATUS_ACTIVE, $isPublic = true)
    {
        $user = new User($name);
        $user->setStatus($status);
        $user->setPublic($isPublic);
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }
}
