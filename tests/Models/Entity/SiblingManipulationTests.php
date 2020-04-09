<?php

namespace Franzose\ClosureTable\Tests\Models\Entity;

use Franzose\ClosureTable\Models\Entity;
use Franzose\ClosureTable\Tests\BaseTestCase;
use Franzose\ClosureTable\Tests\Page;

final class SiblingManipulationTests extends BaseTestCase
{
    public function testAddSibling()
    {
        $entity = Entity::find(15);

        $entity->addSibling(new Page(['title' => 'Foo!']));

        $sibling = $entity->getNextSibling();
        static::assertEquals(4, $sibling->position);
        static::assertEquals('Foo!', $sibling->title);
    }

    public function testAddSiblingAtPosition()
    {
        $entity = Entity::find(15);
        $sibling = new Page(['title' => 'Foo!']);

        $entity->addSibling($sibling, 1);

        static::assertEquals(16, $sibling->getKey());
        static::assertEquals(1, $sibling->position);
        static::assertModelAttribute('position', [
            10 => 0,
            16 => 1,
            13 => 2,
            14 => 3,
            15 => 4
        ]);
    }

    public function testAddSiblings()
    {
        $entity = Entity::find(15);
        $entity->addSiblings([
            new Page(['title' => 'One']),
            new Page(['title' => 'Two']),
            new Page(['title' => 'Three']),
            new Page(['title' => 'Four']),
        ]);

        $siblings = $entity->getNextSiblings();

        static::assertCount(4, $siblings);
        static::assertEquals(4, $siblings->get(0)->position);
        static::assertEquals(5, $siblings->get(1)->position);
        static::assertEquals(6, $siblings->get(2)->position);
        static::assertEquals(7, $siblings->get(3)->position);
    }

    public function testAddSiblingsFromPosition()
    {
        $entity = Entity::find(15);

        $entity->addSiblings([
            new Page(['title' => 'One']),
            new Page(['title' => 'Two']),
            new Page(['title' => 'Three']),
            new Page(['title' => 'Four']),
        ], 1);

        $siblings = $entity->getSiblingsRange(1, 4);

        static::assertEquals(0, Entity::find(10)->position);
        static::assertEquals('One', $siblings->get(0)->title);
        static::assertEquals('Two', $siblings->get(1)->title);
        static::assertEquals('Three', $siblings->get(2)->title);
        static::assertEquals('Four', $siblings->get(3)->title);
        static::assertModelAttribute('position', [
            10 => 0,
            13 => 5,
            14 => 6,
            15 => 7
        ]);
    }
}
