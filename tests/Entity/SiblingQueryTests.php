<?php
declare(strict_types=1);

namespace Franzose\ClosureTable\Tests\Entity;

use Franzose\ClosureTable\Entity;
use Franzose\ClosureTable\EntityCollection;
use Franzose\ClosureTable\Tests\BaseTestCase;

class SiblingQueryTests extends BaseTestCase
{
    public function testGetSiblings(): void
    {
        $entity = Entity::find(13);

        $siblings = $entity->getSiblings();

        static::assertInstanceOf(EntityCollection::class, $siblings);
        static::assertCount(3, $siblings);
        static::assertEquals(10, $siblings->get(0)->getKey());
        static::assertEquals(14, $siblings->get(1)->getKey());
        static::assertEquals(15, $siblings->get(2)->getKey());
    }

    public function testGetSiblingsForRoot(): void
    {
        $entity = Entity::find(1);

        $siblings = $entity->getSiblings();

        static::assertCount(8, $siblings);
        static::assertEquals([2, 3, 4, 5, 6, 7, 8, 9], $siblings->modelKeys());
    }

    public function testsCountSiblings(): void
    {
        static::assertEquals(3, Entity::find(13)->countSiblings());
    }

    public function testsHasSiblings(): void
    {
        static::assertTrue(Entity::find(13)->hasSiblings());
    }

    public function testsGetNeighbors(): void
    {
        $entity = Entity::find(13);

        $neighbors = $entity->getNeighbors();

        static::assertCount(2, $neighbors);
        static::assertEquals(10, $neighbors->get(0)->getKey());
        static::assertEquals(14, $neighbors->get(1)->getKey());
    }

    public function testsGetSiblingAt(): void
    {
        $entity = Entity::find(13);

        $first = $entity->getSiblingAt(0);
        $third = $entity->getSiblingAt(2);

        static::assertEquals(10, $first->getKey());
        static::assertEquals(14, $third->getKey());
    }

    public function testGetFirstSibling(): void
    {
        static::assertEquals(10, Entity::find(13)->getFirstSibling()->getKey());
    }

    public function testGetLastSibling(): void
    {
        static::assertEquals(15, Entity::find(13)->getLastSibling()->getKey());
    }

    public function testGetPrevSibling(): void
    {
        static::assertEquals(14, Entity::find(15)->getPrevSibling()->getKey());
    }

    public function testGetPrevSiblings(): void
    {
        $entity = Entity::find(15);

        $siblings = $entity->getPrevSiblings();

        static::assertCount(3, $siblings);
        static::assertEquals(10, $siblings->get(0)->getKey());
        static::assertEquals(13, $siblings->get(1)->getKey());
        static::assertEquals(14, $siblings->get(2)->getKey());
    }

    public function testsCountPrevSiblings(): void
    {
        static::assertEquals(3, Entity::find(15)->countPrevSiblings());
        static::assertEquals(0, Entity::find(1)->countPrevSiblings());
    }

    public function testsHasPrevSiblings(): void
    {
        static::assertTrue(Entity::find(15)->hasPrevSiblings());
        static::assertFalse(Entity::find(1)->hasPrevSiblings());
    }

    public function testGetNextSibling(): void
    {
        static::assertEquals(13, Entity::find(10)->getNextSibling()->getKey());
    }

    public function testGetNextSiblings(): void
    {
        $entity = Entity::find(10);

        $siblings = $entity->getNextSiblings();

        static::assertCount(3, $siblings);
        static::assertEquals(13, $siblings->get(0)->getKey());
        static::assertEquals(14, $siblings->get(1)->getKey());
        static::assertEquals(15, $siblings->get(2)->getKey());
    }

    public function testCountNextSiblings(): void
    {
        static::assertEquals(3, Entity::find(10)->countNextSiblings());
        static::assertEquals(0, Entity::find(15)->countNextSiblings());
    }

    public function testsHasNextSiblings(): void
    {
        static::assertTrue(Entity::find(10)->hasNextSiblings());
        static::assertFalse(Entity::find(15)->hasNextSiblings());
    }

    public function testGetSiblingsRange(): void
    {
        $entity = Entity::find(15);

        $siblings = $entity->getSiblingsRange(1, 2);

        static::assertCount(2, $siblings);
        static::assertEquals(13, $siblings->get(0)->getKey());
        static::assertEquals(14, $siblings->get(1)->getKey());
    }

    public function testGetSiblingsOpenRange(): void
    {
        static::assertCount(2, Entity::find(15)->getSiblingsRange(1));
    }

    public function testSiblingOfScope(): void
    {
        static::assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9], Entity::siblingOf(9)->get()->modelKeys());
        static::assertEquals([10, 13, 14, 15], Entity::siblingOf(10)->get()->modelKeys());
    }

    public function testSiblingsOfScope(): void
    {
        static::assertEquals([1, 2, 3, 4, 5, 6, 7, 8], Entity::siblingsOf(9)->get()->modelKeys());
        static::assertEquals([10, 14, 15], Entity::siblingsOf(13)->get()->modelKeys());
    }

    public function testNeighborsOfScope(): void
    {
        static::assertEquals([7, 9], Entity::neighborsOf(8)->get()->modelKeys());
        static::assertEquals([10, 14], Entity::neighborsOf(13)->get()->modelKeys());
    }

    public function testSiblingOfAtScope(): void
    {
        static::assertEquals([2], Entity::siblingOfAt(9, 1)->get()->modelKeys());
        static::assertEquals([14], Entity::siblingOfAt(10, 2)->get()->modelKeys());
    }


    public function testFirstSiblingOfScope(): void
    {
        static::assertEquals([1], Entity::firstSiblingOf(9)->get()->modelKeys());
        static::assertEquals([10], Entity::firstSiblingOf(15)->get()->modelKeys());
    }

    public function testLastSiblingOfScope(): void
    {
        static::assertEquals([9], Entity::lastSiblingOf(1)->get()->modelKeys());
        static::assertEquals([15], Entity::lastSiblingOf(10)->get()->modelKeys());
    }

    public function testPrevSiblingOfScope(): void
    {
        static::assertEquals([8], Entity::prevSiblingOf(9)->get()->modelKeys());
        static::assertEquals([14], Entity::prevSiblingOf(15)->get()->modelKeys());
    }

    public function testPrevSiblingsOfScope(): void
    {
        static::assertEquals([1, 2, 3, 4, 5, 6, 7, 8], Entity::prevSiblingsOf(9)->get()->modelKeys());
        static::assertEquals([10, 13, 14], Entity::prevSiblingsOf(15)->get()->modelKeys());
    }

    public function testNextSiblingOfScope(): void
    {
        static::assertEquals([9], Entity::nextSiblingOf(8)->get()->modelKeys());
        static::assertEquals([15], Entity::nextSiblingOf(14)->get()->modelKeys());
    }

    public function testNextSiblingsOfScope(): void
    {
        static::assertEquals([2, 3, 4, 5, 6, 7, 8, 9], Entity::nextSiblingsOf(1)->get()->modelKeys());
        static::assertEquals([13, 14, 15], Entity::nextSiblingsOf(10)->get()->modelKeys());
    }

    public function testSiblingsRangeOfScope(): void
    {
        static::assertEquals([6, 7, 8, 9], Entity::siblingsRangeOf(1, 5)->get()->modelKeys());
        static::assertEquals([3, 4, 5, 6], Entity::siblingsRangeOf(1, 2, 5)->get()->modelKeys());
        static::assertEquals([13, 14, 15], Entity::siblingsRangeOf(10, 1)->get()->modelKeys());
        static::assertEquals([13, 14, 15], Entity::siblingsRangeOf(10, 1, 3)->get()->modelKeys());
    }
}
