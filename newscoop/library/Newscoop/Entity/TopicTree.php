<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Entity;

/**
 * @Entity
 * @Table(name="Topics")
 */
class TopicTree
{
    /**
     * @Id
     * @Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @Column(type="integer")
     * @var int
     */
    private $node_left;

    /**
     * @Column(type="integer")
     * @var int
     */
    private $node_right;

    /**
     * @param int $id
     * @param int $left
     */
    public function __construct($id, $left)
    {
        $this->id = (int) $id;
        $this->node_left = (int) $left;
        $this->node_right = $this->node_left + 1;
    }

    /**
     * Get left
     *
     * @return int
     */
    public function getLeft()
    {
        return $this->node_left;
    }

    /**
     * Set right
     *
     * @param int $right
     * @return void
     */
    public function setRight($right)
    {
        $this->node_right = (int) $right;
    }

    /**
     * Get right
     *
     * @return int
     */
    public function getRight()
    {
        return $this->node_right;
    }
}
