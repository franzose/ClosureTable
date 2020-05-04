<?php

namespace Franzose\ClosureTable\Tests\Models\Entity;

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

    public function testSiblingOfScope()
    {
        static::assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9], Entity::siblingOf(9)->get()->modelKeys());
        static::assertEquals([10, 13, 14, 15], Entity::siblingOf(10)->get()->modelKeys());
    }

    public function testSiblingsOfScope()
    {
        static::assertEquals([1, 2, 3, 4, 5, 6, 7, 8], Entity::siblingsOf(9)->get()->modelKeys());
        static::assertEquals([10, 14, 15], Entity::siblingsOf(13)->get()->modelKeys());
    }

    public function testNeighborsOfScope()
    {
        static::assertEquals([7, 9], Entity::neighborsOf(8)->get()->modelKeys());
        static::assertEquals([10, 14], Entity::neighborsOf(13)->get()->modelKeys());
    }

    public function testSiblingOfAtScope()
    {
        static::assertEquals([2], Entity::siblingOfAt(9, 1)->get()->modelKeys());
        static::assertEquals([14], Entity::siblingOfAt(10, 2)->get()->modelKeys());
    }


    public function testFirstSiblingOfScope()
    {
        static::assertEquals([1], Entity::firstSiblingOf(9)->get()->modelKeys());
        static::assertEquals([10], Entity::firstSiblingOf(15)->get()->modelKeys());
    }

    public function testLastSiblingOfScope()
    {
        static::assertEquals([9], Entity::lastSiblingOf(1)->get()->modelKeys());
        static::assertEquals([15], Entity::lastSiblingOf(10)->get()->modelKeys());
    }

    public function testPrevSiblingOfScope()
    {
        static::assertEquals([8], Entity::prevSiblingOf(9)->get()->modelKeys());
        static::assertEquals([14], Entity::prevSiblingOf(15)->get()->modelKeys());
    }

    public function testPrevSiblingsOfScope()
    {
        static::assertEquals([1, 2, 3, 4, 5, 6, 7, 8], Entity::prevSiblingsOf(9)->get()->modelKeys());
        static::assertEquals([10, 13, 14], Entity::prevSiblingsOf(15)->get()->modelKeys());
    }

    public function testNextSiblingOfScope()
    {
        static::assertEquals([9], Entity::nextSiblingOf(8)->get()->modelKeys());
        static::assertEquals([15], Entity::nextSiblingOf(14)->get()->modelKeys());
    }

    public function testNextSiblingsOfScope()
    {
        static::assertEquals([2, 3, 4, 5, 6, 7, 8, 9], Entity::nextSiblingsOf(1)->get()->modelKeys());
        static::assertEquals([13, 14, 15], Entity::nextSiblingsOf(10)->get()->modelKeys());
    }

    public function testSiblingsRangeOfScope()
    {
        static::assertEquals([6, 7, 8, 9], Entity::siblingsRangeOf(1, 5)->get()->modelKeys());
        static::assertEquals([3, 4, 5, 6], Entity::siblingsRangeOf(1, 2, 5)->get()->modelKeys());
        static::assertEquals([13, 14, 15], Entity::siblingsRangeOf(10, 1)->get()->modelKeys());
        static::assertEquals([13, 14, 15], Entity::siblingsRangeOf(10, 1, 3)->get()->modelKeys());
    }
}
