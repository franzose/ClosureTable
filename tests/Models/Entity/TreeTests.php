<?php

namespace Franzose\ClosureTable\Tests\Models\Entity;

use Franzose\ClosureTable\Extensions\Collection;
use Franzose\ClosureTable\Models\ClosureTable;
use Franzose\ClosureTable\Models\Entity;
use Franzose\ClosureTable\Tests\BaseTestCase;
use Franzose\ClosureTable\Tests\Page;

class TreeTests extends BaseTestCase
{
    public function testDeleteSubtree()
    {
        $entity = Entity::find(9);
        $entity->deleteSubtree();

        static::assertEquals(1, Entity::whereBetween('id', [9, 15])->count());
        static::assertEquals(8, Entity::whereBetween('id', [1, 8])->count());
    }

    public function testDeleteSubtreeWithAncestor()
    {
        $entity = Entity::find(9);
        $entity->deleteSubtree(true);

        static::assertEquals(0, Entity::whereBetween('id', [9, 15])->count());
        static::assertEquals(8, Entity::whereBetween('id', [1, 8])->count());
    }

    public function testForceDeleteSubtree()
    {
        $entity = Entity::find(9);
        $entity->deleteSubtree(false, true);

        static::assertEquals(1, Entity::whereBetween('id', [9, 15])->count());
        static::assertEquals(1, ClosureTable::whereBetween('ancestor', [9, 15])->count());
    }

    public function testForceDeleteDeepSubtree()
    {
        Entity::find(9)->moveTo(0, 8);
        Entity::find(8)->moveTo(0, 7);
        Entity::find(7)->moveTo(0, 6);
        Entity::find(6)->moveTo(0, 5);
        Entity::find(5)->moveTo(0, 4);
        Entity::find(4)->moveTo(0, 3);
        Entity::find(3)->moveTo(0, 2);
        Entity::find(2)->moveTo(0, 1);

        Entity::find(1)->deleteSubtree(false, true);

        static::assertEquals(1, Entity::whereBetween('id', [1, 9])->count());
        static::assertEquals(1, ClosureTable::whereBetween('ancestor', [1, 9])->count());
    }

    public function testForceDeleteSubtreeWithSelf()
    {
        $entity = Entity::find(9);
        $entity->deleteSubtree(true, true);

        static::assertEquals(0, Entity::whereBetween('id', [9, 15])->count());
        static::assertEquals(0, ClosureTable::whereBetween('ancestor', [9, 15])->count());
    }

    public function testCreateFromArray()
    {
        $array = [
            [
                'id' => 90,
                'title' => 'About',
                'position' => 0,
                'children' => [
                    [
                        'id' => 93,
                        'title' => 'Testimonials'
                    ]
                ]
            ],
            [
                'id' => 91,
                'title' => 'Blog',
                'position' => 1
            ],
            [
                'id' => 92,
                'title' => 'Portfolio',
                'position' => 2
            ],
        ];

        $pages = Page::createFromArray($array);

        static::assertCount(3, $pages);

        $pageZero = $pages->get(0);
        static::assertEquals(90, $pageZero->getKey());
        static::assertEquals(91, $pages->get(1)->getKey());
        static::assertEquals(92, $pages->get(2)->getKey());
        static::assertEquals(93, $pageZero->getChildAt(0)->getKey());
    }

    public function testCreateFromArrayBug81()
    {
        $array = [
            [
                'title' => 'About',
                'children' => [
                    [
                        'title' => 'Testimonials',
                        'children' => [
                            [
                                'title' => 'child 1',
                            ],
                            [
                                'title' => 'child 2',
                            ],
                        ]
                    ]
                ]
            ],
            [
                'title' => 'Blog',
            ],
            [
                'title' => 'Portfolio',
            ],
        ];

        $pages = Page::createFromArray($array);

        $about = $pages[0];
        static::assertEquals('About', $about->title);
        static::assertEquals(1, $about->countChildren());
        static::assertEquals(16, $about->getKey());

        $blog = $pages[1];
        static::assertEquals('Blog', $blog->title);
        static::assertEquals(0, $blog->countChildren());
        static::assertEquals(20, $blog->getKey());

        $portfolio = $pages[2];
        static::assertEquals('Portfolio', $portfolio->title);
        static::assertEquals(0, $portfolio->countChildren());
        static::assertEquals(21, $portfolio->getKey());

        $pages = $pages[0]->getChildren();

        $testimonials = $pages[0];
        static::assertEquals('Testimonials', $testimonials->title);
        static::assertEquals(2, $testimonials->countChildren());
        static::assertEquals(17, $testimonials->getKey());

        $pages = $pages[0]->getChildren();

        $child1 = $pages[0];
        static::assertEquals('child 1', $child1->title);
        static::assertEquals(0, $child1->countChildren());
        static::assertEquals(18, $child1->getKey());

        $child2 = $pages[1];
        static::assertEquals('child 2', $child2->title);
        static::assertEquals(0, $child2->countChildren());
        static::assertEquals(19, $child2->getKey());
    }

    /**
     * @link https://github.com/franzose/ClosureTable/issues/239
     */
    public function testCreateFromArrayIssue239()
    {
        Page::createFromArray([
            [
                'id' => 100,
                'children' => [
                    ['id' => 200],
                    ['id' => 300],
                    ['id' => 400],
                    ['id' => 500],
                    [
                        'id' => 600,
                        'children' => [
                            ['id' => 700],
                            ['id' => 800],
                        ]
                    ],
                ]
            ]
        ]);

        /** @var Page $page */
        $page = Page::find(100);

        /** @var Collection|Page[] $children */
        $children = $page->getChildren();

        static::assertCount(5, $children);
        static::assertEquals(200, $children->get(0)->id);
        static::assertEquals(300, $children->get(1)->id);
        static::assertEquals(400, $children->get(2)->id);
        static::assertEquals(500, $children->get(3)->id);
        static::assertEquals(600, $children->get(4)->id);

        $childrenOf600 = $children->get(4)->getChildren();
        static::assertCount(2, $childrenOf600);
        static::assertEquals(700, $childrenOf600->get(0)->id);
        static::assertEquals(800, $childrenOf600->get(1)->id);
    }
}
