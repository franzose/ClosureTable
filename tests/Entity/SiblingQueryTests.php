<?php

namespace Franzose\ClosureTable\Tests\Entity;

use Franzose\ClosureTable\Extensions\Collection;
use Franzose\ClosureTable\Models\Entity;
use Franzose\ClosureTable\Tests\BaseTestCase;

class SiblingQueryTests extends BaseTestCase
{
    public function testGetSiblings()
    {
        $entity = Entity::find(13);

        $siblings = $entity->getSiblings();

        static::assertInstanceOf(Collection::class, $siblings);
        static::assertCount(3, $siblings);
        static::assertEquals(10, $siblings->get(0)->getKey());
        static::assertEquals(14, $siblings->get(1)->getKey());
        static::assertEquals(15, $siblings->get(2)->getKey());
    }

    public function testsCountSiblings()
    {
        static::assertEquals(3, Entity::find(13)->countSiblings());
    }

    public function testsHasSiblings()
    {
        static::assertTrue(Entity::find(13)->hasSiblings());
    }

    public function testsGetNeighbors()
    {
        $entity = Entity::find(13);

        $neighbors = $entity->getNeighbors();

        static::assertCount(2, $neighbors);
        static::assertEquals(10, $neighbors->get(0)->getKey());
        static::assertEquals(14, $neighbors->get(1)->getKey());
    }

    public function testsGetSiblingAt()
    {
        $entity = Entity::find(13);

        $first = $entity->getSiblingAt(0);
        $third = $entity->getSiblingAt(2);

        static::assertEquals(10, $first->getKey());
        static::assertEquals(14, $third->getKey());
    }

    public function testGetFirstSibling()
    {
        static::assertEquals(10, Entity::find(13)->getFirstSibling()->getKey());
    }

    public function testGetLastSibling()
    {
        static::assertEquals(15, Entity::find(13)->getLastSibling()->getKey());
    }

    public function testGetPrevSibling()
    {
        static::assertEquals(14, Entity::find(15)->getPrevSibling()->getKey());
    }

    public function testGetPrevSiblings()
    {
        $entity = Entity::find(15);

        $siblings = $entity->getPrevSiblings();

        static::assertCount(3, $siblings);
        static::assertEquals(10, $siblings->get(0)->getKey());
        static::assertEquals(13, $siblings->get(1)->getKey());
        static::assertEquals(14, $siblings->get(2)->getKey());
    }

    public function testsCountPrevSiblings()
    {
        static::assertEquals(3, Entity::find(15)->countPrevSiblings());
        static::assertEquals(0, Entity::find(1)->countPrevSiblings());
    }

    public function testsHasPrevSiblings()
    {
        static::assertTrue(Entity::find(15)->hasPrevSiblings());
        static::assertFalse(Entity::find(1)->hasPrevSiblings());
    }

    public function testGetNextSibling()
    {
        static::assertEquals(13, Entity::find(10)->getNextSibling()->getKey());
    }

    public function testGetNextSiblings()
    {
        $entity = Entity::find(10);

        $siblings = $entity->getNextSiblings();

        static::assertCount(3, $siblings);
        static::assertEquals(13, $siblings->get(0)->getKey());
        static::assertEquals(14, $siblings->get(1)->getKey());
        static::assertEquals(15, $siblings->get(2)->getKey());
    }

    public function testCountNextSiblings()
    {
        static::assertEquals(3, Entity::find(10)->countNextSiblings());
        static::assertEquals(0, Entity::find(15)->countNextSiblings());
    }

    public function testsHasNextSiblings()
    {
        static::assertTrue(Entity::find(10)->hasNextSiblings());
        static::assertFalse(Entity::find(15)->hasNextSiblings());
    }

    public function testGetSiblingsRange()
    {
        $entity = Entity::find(15);

        $siblings = $entity->getSiblingsRange(1, 2);

        static::assertCount(2, $siblings);
        static::assertEquals(13, $siblings->get(0)->getKey());
        static::assertEquals(14, $siblings->get(1)->getKey());
    }

    public function testGetSiblingsOpenRange()
    {
        static::assertCount(2, Entity::find(15)->getSiblingsRange(1));
    }
}
