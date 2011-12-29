<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Tree;

class TreeServiceTest extends \RepositoryTestCase
{
    public function setUp()
    {
        parent::setUp('Newscoop\Entity\TopicTree');
    }

    public function testSave()
    {
        $service = new TreeService($this->em);
        $xml = simplexml_load_file(__DIR__  . '/tree.xml');
        $service->save($xml);

        $tree = $service->find();
        $this->assertEquals(5, count($tree));

        $this->assertEquals(1, $tree[0]->getLeft());
        $this->assertEquals(2, $tree[0]->getRight());

        $this->assertEquals(3, $tree[1]->getLeft());
        $this->assertEquals(8, $tree[1]->getRight());

        $this->assertEquals(4, $tree[2]->getLeft());
        $this->assertEquals(5, $tree[2]->getRight());

        $this->assertEquals(6, $tree[3]->getLeft());
        $this->assertEquals(7, $tree[3]->getRight());

        $this->assertEquals(9, $tree[4]->getLeft());
        $this->assertEquals(10, $tree[4]->getRight());
    }

    public function testClearBeforeSave()
    {
        $service = new TreeService($this->em);
        $xml = simplexml_load_file(__DIR__ . '/tree.xml');

        $service->save($xml);
        $service->save($xml);

        $this->assertEquals(5, count($service->find()));
    }
}
