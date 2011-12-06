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

class EditorUserServiceTest extends \RepositoryTestCase
{
    /** @var Newscoop\Services\UserService */
    protected $service;

    /** @var Doctrine\ORM\EntityManager */
    protected $em;

    /** @var Newscoop\Entity\Repository\UserRepository */
    protected $repository;

    /** @var array */
    protected $users = array();

    public function setUp()
    {
        parent::setUp('Newscoop\Entity\User', 'Newscoop\Entity\Acl\Role', 'Newscoop\Entity\UserAttribute', 'Newscoop\Entity\User\Group', 'Newscoop\Entity\Author');

        $this->repository = $this->em->getRepository('Newscoop\Entity\User');

        $auth = $this->getMockBuilder('Zend_Auth')
            ->disableOriginalConstructor()
            ->getMock();

        $roles = array(
            'editors1' => new Group('editors1'),
            'editors2' => new Group('editors2'),
            'admins' => new Group('admins'),
        );

        $this->em->persist($roles['admins']);
        $this->em->persist($roles['editors1']);
        $this->em->persist($roles['editors2']);
        $this->em->flush();

        $this->service = new UserService(array(
            'editorRoles' => array($roles['editors1']->getId(), $roles['editors2']->getId()),
        ), $this->em, $auth);

        $this->users = array(
            'public_editor' => $this->service->save(array(
                'username' => 'public_editor',
                'email' => 'public_editor',
                'status' => User::STATUS_ACTIVE,
                'is_public' => 1,
                'is_admin' => 1,
                'user_type' => array($roles['editors1']->getId()),
            )),
            'public_editor_2' => $this->service->save(array(
                'username' => 'public_editor_2',
                'email' => 'public_editor_2',
                'status' => User::STATUS_ACTIVE,
                'is_public' => 1,
                'is_admin' => 1,
                'user_type' => array($roles['editors2']->getId()),
            )),
            'private_editor' => $this->service->save(array(
                'username' => 'private_editor',
                'email' => 'private_editor',
                'status' => User::STATUS_ACTIVE,
                'is_admin' => 1,
                'user_type' => array($roles['editors1']->getId()),
            )),
            'public_admin' => $this->service->save(array(
                'username' => 'public_admin',
                'email' => 'public_admin',
                'status' => User::STATUS_ACTIVE,
                'is_public' => 1,
                'is_admin' => 1,
                'user_type' => array($roles['admins']->getId()),
            )),
            'private_admin' => $this->service->save(array(
                'username' => 'private_admin',
                'email' => 'private_admin',
                'status' => User::STATUS_ACTIVE,
                'is_admin' => 1,
                'user_type' => array($roles['admins']->getId()),
            )),
            'public_user' => $this->service->save(array(
                'username' => 'public_user',
                'email' => 'public_user',
                'status' => User::STATUS_ACTIVE,
                'is_public' => 1,
            )),
            'private_user' => $this->service->save(array(
                'username' => 'private_user',
                'email' => 'private_user',
                'status' => User::STATUS_ACTIVE,
            )),
            'public_admin_editor' => $this->service->save(array(
                'username' => 'public_admin_editor',
                'email' => 'public_admin_editor',
                'status' => User::STATUS_ACTIVE,
                'is_public' => 1,
                'user_type' => array($roles['admins']->getId(), $roles['editors1']->getId()),
            )),
        );
    }

    public function testGetEditors()
    {
        $editors = $this->service->findEditors();
        $this->assertEquals(3, count($editors));
        $this->assertEquals('public_admin_editor', $editors[0]->getUsername());
        $this->assertEquals('public_editor', $editors[1]->getUsername());
        $this->assertEquals('public_editor_2', $editors[2]->getUsername());
    }

    public function testGetEditorsCount()
    {
        $this->assertEquals(3, $this->service->getEditorsCount());
    }

    public function testGetActiveUsers()
    {
        $users = $this->service->getActiveUsers();
        $this->assertEquals(2, count($users));
        $this->assertEquals(2, $this->service->getActiveUsers(true));
        $this->assertEquals('public_admin', $users[0]->getUsername());
        $this->assertEquals('public_user', $users[1]->getUsername());
    }

    /**
     * @ticket CS-3911
     */
    public function testRemovingRelatedAuthor()
    {
        $author = new Author('foo', 'bar');
        $this->em->persist($author);
        $this->em->flush();

        $this->users['public_editor']->setAuthor($author);
        $this->em->flush();

        $this->assertEquals(2, count($this->service->getActiveUsers()));
        $this->assertEquals(3, count($this->service->findEditors()));

        $this->em->remove($author);
        $this->em->flush();

        $this->assertEquals(2, count($this->service->getActiveUsers()));
        $this->assertEquals(3, count($this->service->findEditors()));
    }
}
