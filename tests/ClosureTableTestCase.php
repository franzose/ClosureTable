<?php require_once('tests/app.php');

use \Illuminate\Support\Facades\Schema;
use Franzose\ClosureTable\ClosureTable;

class ClosureTableTestCase extends \PHPUnit_Framework_TestCase {

    public function setUp()
    {
        parent::setUp();

        Schema::create('pages', function($table){
            $table->increments('id')->unsigned();
            $table->string('title', 250);
            $table->string('excerpt', 500);
            $table->text('content');
            $table->integer('position')->unsigned();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });

        Schema::create('pages_closure', function($table){
            $table->increments('ctid');
            $table->integer('ancestor')->unsigned();
            $table->integer('descendant')->unsigned();
            $table->integer('depth')->unsigned();
        });
    }

    public function tearDown()
    {
        Schema::drop('pages');
        Schema::drop('pages_closure');
    }

    /**
     * Creates new page for further testing.
     *
     * @return Franzose\ClosureTable\Entity|static
     */
    protected function prepareTestedEntity()
    {
        $page = Page::create(array(
            'title' => 'Test Title',
            'excerpt' => 'Test Excerpt',
            'content' => 'Test content'
        ));

        return $page;
    }

    /**
     * Creates three nested pages.
     *
     * @return array
     */
    protected function prepareTestedRelationships()
    {
        $page = Page::create(array(
            'title' => 'Test Title',
            'excerpt' => 'Test Excerpt',
            'content' => 'Test content'
        ));

        $child = Page::create(array(
            'title' => 'Child Page Test Title',
            'excerpt' => 'Child Page Test Excerpt',
            'content' => 'Child Page Test content'
        ));

        $child = $page->appendChild($child, 0, true);

        $grandchild = Page::create(array(
            'title' => 'GrandChild Page Test Title',
            'excerpt' => 'GrandChild Page Test Excerpt',
            'content' => 'GrandChild Page Test content'
        ));

        $grandchild = $child->appendChild($grandchild, 0, true);

        return array($page, $child, $grandchild);
    }

    /**
     * Creates a page and its four child siblings.
     *
     * @return array
     */
    protected function prepareTestedSiblings()
    {
        $page = Page::create(array(
            'title' => 'Test Title',
            'excerpt' => 'Test Excerpt',
            'content' => 'Test content'
        ));

        $child1 = $page->appendChild(new Page(array(
            'title' => 'Test Title',
            'excerpt' => 'Test Excerpt',
            'content' => 'Test content'
        )), 0, true);

        $child2 = $page->appendChild(new Page(array(
            'title' => 'Test Title',
            'excerpt' => 'Test Excerpt',
            'content' => 'Test content'
        )), 1, true);

        $child3 = $page->appendChild(new Page(array(
            'title' => 'Test Title',
            'excerpt' => 'Test Excerpt',
            'content' => 'Test content'
        )), 2, true);

        $child4 = $page->appendChild(new Page(array(
            'title' => 'Test Title',
            'excerpt' => 'Test Excerpt',
            'content' => 'Test content'
        )), 3, true);

        return array($page, $child1, $child2, $child3, $child4);
    }

    /**
     * Tests new Entity object properties for the right values.
     *
     * @return void
     */
    public function testNewEntity()
    {
        $page = new Page;
        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $page);
        $this->assertEquals('pages', $page->getTable());
        $this->assertEquals('id', $page->getKeyName());
        $this->assertEquals('pages.id', $page->getQualifiedKeyName());
        $this->assertEquals(null, $page->getParent());
        $this->assertEquals(null, $page->closuretable);
    }

    /**
     * Tests insertion of a single Entity into the database.
     * Makes sure of closure table data correctness.
     *
     * @return void
     */
    public function testInsertSingle()
    {
        $page = $this->prepareTestedEntity();

        $this->assertEquals(true, $page->exists);
        $this->assertEquals(1, Page::count());
        $this->assertTrue(isset($page->id));
        $this->assertInstanceOf('DateTime', $page->created_at);
        $this->assertEquals('Test Title', $page->title);
        $this->assertEquals('Test Excerpt', $page->excerpt);
        $this->assertEquals('Test content', $page->content);

        $closure = $page->closuretable();
        $result = $closure->where('descendant', '=', $page->id)->get();

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Relations\HasOne', $closure);
        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $result);
        $this->assertEquals(1, $result->count());

        $closure = $result->first();

        $this->assertEquals($page->id, $closure->ancestor);
        $this->assertEquals($page->id, $closure->descendant);
        $this->assertEquals(0, $closure->depth);
    }

    /**
     * Tests deletion of a single Entity.
     * Makes sure of closure table data correctness.
     *
     * @return void
     */
    public function testDeleteSingle()
    {
        $page = $this->prepareTestedEntity();

        $pid = $page->id;
        $page->delete();

        $closure = new ClosureTable;
        $result = $closure->where('descendant', '=', $pid)->get();

        $this->assertEquals(0, Page::count());
        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $result);
        $this->assertEquals(0, $result->count());
    }

    /**
     *
     * @return void
     */
    public function testDeleteSubtree()
    {
        //@todo: implement
    }

    /**
     *
     * @return void
     */
    public function testDeleteDescendants()
    {
        //@todo: implement
    }

    /**
     * Tests 'buildAncestorsQuery' method.
     * Checks the correctness of SQL it produces because that SQL is further used.
     *
     * @return void
     */
    public function testBuildAncestorsQuery()
    {
        // all 'build...Query' methods are protected in the Entity class,
        // so we will use a reflection instead.
        $reflection = new ReflectionClass('Page');
        $method = $reflection->getMethod('buildAncestorsQuery');
        $method->setAccessible(true);
        $result = $method->invoke(new Page);

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Builder', $result);

        // real SQL dump would contain a descendant value instead of 'is null' statement
        // so we can assume that 'is null' here is the right behaviour
        $resultsql = $result->toSql();
        $rightsql = 'select "pages".* from "pages" inner join "pages_closure" on "pages_closure"."ancestor" = "pages"."id" where "pages_closure"."descendant" is null and "pages_closure"."depth" > ?';

        $this->assertEquals($rightsql, $resultsql);
    }

    /**
     * Tests 'ancestors' method.
     *
     * @return void
     */
    public function testAncestors()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();
        $ancestors = $grandchild->ancestors();

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $ancestors);
        $this->assertCount(2, $ancestors);
        $this->assertEquals($page->id, $child->getParent()->id);
    }

    /**
     * Tests 'hasAncestors' method.
     *
     * @return void
     */
    public function testHasAncestors()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();
        $hasAncestors = $grandchild->hasAncestors();

        $this->assertInternalType('bool', $hasAncestors);
        $this->assertTrue($hasAncestors);
    }

    /**
     * Tests 'countAncestors' method.
     *
     * @return void
     */
    public function testCountAncestors()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();
        $number = $grandchild->countAncestors();

        $this->assertInternalType('integer', $number);
        $this->assertEquals(2, $number);
    }

    /**
     * Tests 'buildChildrenQuery' method.
     * Checks the correctness of SQL it produces because that SQL is further used.
     *
     * @return void
     */
    public function testBuildChildrenQuery()
    {
        $page = $this->prepareTestedEntity();
        $reflection = new ReflectionClass('Page');
        $method = $reflection->getMethod('buildChildrenQuery');
        $method->setAccessible(true);
        $result = $method->invoke($page);

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Builder', $result);

        $resultsql = $result->toSql();
        $rightsql = 'select * from "pages" inner join "pages_closure" on "pages_closure"."descendant" = "pages"."id" where "pages_closure"."ancestor" = ? and "pages_closure"."depth" = ?';

        $this->assertEquals($rightsql, $resultsql);
    }

    /**
     * Tests 'children' method.
     *
     * @return void
     */
    public function testChildren()
    {
        list($page) = $this->prepareTestedRelationships();
        $pageChildren = $page->children();

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $pageChildren);
        $this->assertCount(1, $pageChildren);
    }

    /**
     * Tests 'hasChildren' method.
     *
     * @return void
     */
    public function testHasChildren()
    {
        list($page) = $this->prepareTestedRelationships();
        $hasChildren = $page->hasChildren();

        $this->assertInternalType('bool', $hasChildren);
        $this->assertTrue($hasChildren);
    }

    /**
     * Tests 'countChildren' method.
     *
     * @return void
     */
    public function testCountChildren()
    {
        list($page) = $this->prepareTestedRelationships();
        $number = $page->countChildren();

        $this->assertInternalType('integer', $number);
        $this->assertEquals(1, $number);
    }

    /**
     * Tests 'buildDescendantsQuery' method.
     * Checks the correctness of SQL it produces because that SQL is further used.
     *
     * @return void
     */
    public function testBuildDescendantsQuery()
    {
        $page = $this->prepareTestedEntity();
        $reflection = new ReflectionClass('Page');
        $method = $reflection->getMethod('buildDescendantsQuery');
        $method->setAccessible(true);
        $result = $method->invoke($page);

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Builder', $result);

        $resultsql = $result->toSql();
        $rightsql = 'select * from "pages" inner join "pages_closure" on "pages_closure"."descendant" = "pages"."id" where "pages_closure"."ancestor" = ? and "pages_closure"."depth" > ?';

        $this->assertEquals($rightsql, $resultsql);
    }

    /**
     * Tests 'descendants' method.
     *
     * @return void
     */
    public function testDescendants()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();
        $descendants = $page->descendants();

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $descendants);
        $this->assertCount(2, $descendants);
        $this->assertEquals($child->getParent()->id, $page->id);
        $this->assertEquals($grandchild->getParent()->id, $child->id);
    }

    /**
     * Tests 'hasDescendants' method.
     *
     * @return void
     */
    public function testHasDescendants()
    {
        list($page) = $this->prepareTestedRelationships();
        $hasDescendants = $page->hasDescendants();

        $this->assertInternalType('bool', $hasDescendants);
        $this->assertTrue($hasDescendants);
    }

    /**
     * Tests 'countDescendants' method.
     *
     * @return void
     */
    public function testCountDescendants()
    {
        list($page) = $this->prepareTestedRelationships();
        $number = $page->countDescendants();

        $this->assertInternalType('integer', $number);
        $this->assertEquals(2, $number);
    }

    /**
     * Tests 'getDescendantsIds' method.
     *
     * @return void
     */
    public function testGetDescendantsIds()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();
        $reflection = new ReflectionClass('Page');
        $method = $reflection->getMethod('getDescendantsIds');
        $method->setAccessible(true);
        $result = $method->invoke($page);

        $this->assertInternalType('array', $result);
        $this->assertCount(2, $result);
        $this->assertContains($child->id, $result);
        $this->assertContains($grandchild->id, $result);
    }

    /**
     * Tests 'buildSiblingsQuery' method.
     * Checks the correctness of SQL it produces because that SQL is further used.
     *
     * @return void
     */
    public function testBuildSiblingsQuery()
    {
        $sqlSubstr = 'select "pages".* from "pages" inner join "pages_closure" on "pages_closure"."descendant" = "pages"."id" where "pages_closure"."descendant" <> ? and "pages_closure"."depth" = ? and ';
        $positionIsSql = $sqlSubstr.'"position" :operand ?';
        $positionEqualsSql = $sqlSubstr.'"position" = ?';
        $positionInSql = $sqlSubstr.'"position" in (?, ?)';

        list($page, $child) = $this->prepareTestedSiblings();
        $reflection = new ReflectionClass('Page');
        $method = $reflection->getMethod('buildSiblingsQuery');
        $method->setAccessible(true);

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Builder', $method->invoke($child));

        foreach(array('both', 'prev', 'next') as $direction)
        {
            $queriedAllSql = $method->invokeArgs($child, array($direction, true))->toSql();
            $queriedOneSql = $method->invokeArgs($child, array($direction, false))->toSql();

            switch($direction)
            {
                case 'both':
                    $operand = '<>';
                    break;

                case 'prev':
                    $operand = '<';
                    break;

                case 'next':
                    $operand = '>';
                    break;
            }

            $positionIsSqlWithOperandReplaced = str_replace(':operand', $operand, $positionIsSql);

            if ($direction == 'both')
            {
                $this->assertEquals($positionInSql, $queriedOneSql);
            }
            else
            {
                $this->assertEquals($positionEqualsSql, $queriedOneSql);
            }

            $this->assertEquals($positionIsSqlWithOperandReplaced, $queriedAllSql);
        }
    }

    /**
     * Tests 'siblings' method.
     *
     * @return void
     */
    public function testSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();

        // next siblings
        $child1siblings = $child1->siblings();

        $this->assertCount(3, $child1siblings);
        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $child1siblings[0]);
        $this->assertNotEquals($child1->id, $child1siblings[0]->id);
        $this->assertEquals($child1siblings[1]->position, $child3->position);

        // prev siblings
        $child4siblings = $child4->siblings('all', 'prev');

        $this->assertCount(3, $child4siblings);

        // adjacent siblings (in 1-2-3, they are 1 and 3)
        $child3siblings = $child3->siblings('one', 'both');

        $this->assertCount(2, $child3siblings);
        $this->assertEquals($child2->id, $child3siblings[0]->id);
        $this->assertEquals($child4->id, $child3siblings[1]->id);
    }

    /**
     * Tests 'prevSibling' method.
     *
     * @return void
     */
    public function testPrevSibling()
    {
        list($page, $child1, $child2) = $this->prepareTestedSiblings();
        $prev = $child2->prevSibling();

        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $prev);
        $this->assertEquals($child1->id, $prev->id);
        $this->assertEquals($child1->position, $prev->position);
    }

    /**
     * Tests 'prevSiblings' method.
     *
     * @return void
     */
    public function testPrevSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $prevs = $child4->prevSiblings();

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $prevs);
        $this->assertCount(3, $prevs);
    }

    /**
     * Tests 'hasPrevSiblings' method.
     *
     * @return void
     */
    public function testHasPrevSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $hasPrevs = $child4->hasPrevSiblings();

        $this->assertInternalType('bool', $hasPrevs);
        $this->assertTrue($hasPrevs);
    }

    /**
     * Tests 'countPrevSiblings' method.
     *
     * @return void
     */
    public function testCountPrevSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $number = $child4->countPrevSiblings();

        $this->assertInternalType('integer', $number);
        $this->assertEquals(3, $number);
    }

    /**
     * Tests 'nextSibling' method.
     *
     * @return void
     */
    public function testNextSibling()
    {
        list($page, $child1, $child2) = $this->prepareTestedSiblings();
        $next = $child1->nextSibling();

        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $next);
        $this->assertEquals($child2->id, $next->id);
        $this->assertEquals($child2->position, $next->position);
    }

    /**
     * Tests 'nextSiblings' method.
     *
     * @return void
     */
    public function testNextSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $next = $child1->nextSiblings();

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $next);
        $this->assertCount(3, $next);
    }

    /**
     * Tests 'hasNextSiblings' method.
     *
     * @return void
     */
    public function testHasNextSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $hasNext = $child1->hasNextSiblings();

        $this->assertInternalType('bool', $hasNext);
        $this->assertTrue($hasNext);
    }

    /**
     * Tests 'countNextSiblings' method.
     *
     * @return void
     */
    public function testCountNextSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $number = $child1->countNextSiblings();

        $this->assertInternalType('integer', $number);
        $this->assertEquals(3, $number);
    }

    /**
     * Tests 'hasSiblings' method.
     *
     * @return void
     */
    public function testHasSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $has = $child3->hasSiblings();

        $this->assertInternalType('bool', $has);
        $this->assertTrue($has);
    }

    /**
     * Tests 'countSiblings' method.
     *
     * @return void
     */
    public function testCountSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $number = $child3->countSiblings();

        $this->assertInternalType('integer', $number);
        $this->assertEquals(3, $number);
    }

    /**
     * Tests 'roots' method.
     *
     * @return void
     */
    public function testRoots()
    {
        $pagesIds = array();

        for ($i=0; $i<=5; $i++)
        {
            $pagesIds[] = $this->prepareTestedEntity()->id;
        }

        Page::find($pagesIds[2])->appendChild(new Page(array(
            'title'   => 'test',
            'excerpt' => 'test',
            'content' => 'content'
        )));

        $rootsIds = Page::roots()->lists('id');

        $this->assertEmpty(array_diff($pagesIds, $rootsIds));
    }

    /**
     * Tests 'isRoot' method.
     *
     * @return void
     */
    public function testIsRoot()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();

        $this->assertFalse($child->isRoot());
        $this->assertFalse($grandchild->isRoot());
        $this->assertTrue($page->isRoot());
    }

    /**
     * Tests 'makeRoot' method and 'moveTo' method without 'ancestor' argument.
     *
     * @return void
     */
    public function testMakeRootOrMoveToNull()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();
        $grandchild->makeRoot();
        $child->moveTo();

        $this->assertFalse($grandchild->hasAncestors());
        $this->assertFalse($child->hasAncestors());

        $closure = new ClosureTable;
        $grandchildRows = $closure->where('descendant', '=', $grandchild->id)->where('depth', '>', 0)->count();
        $childRows = $closure->where('descendant', '=', $child->id)->where('depth', '>', 0)->count();

        $this->assertEquals(0, $grandchildRows);
        $this->assertEquals(0, $childRows);
    }

    /**
     *
     *
     * @return void
     */
    public function testTree()
    {
        //@todo: implement
    }

    /**
     *
     *
     * @return void
     */
    public function testMoveToNode()
    {
        //@todo: implement
    }

    /**
     *
     *
     * @return void
     */
    public function testMoveGivenTo()
    {
        //@todo: implement
    }

    /**
     *
     *
     * @return void
     */
    public function testRelationsSyncOnChildInsert()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();

        $closure = new ClosureTable;
        $results = $closure->get()->toArray();

        // we must have six rows in closure table now
        // =============================
        // ancestor | descendant | depth
        //    1     |     1      |   0
        //    2     |     2      |   0
        //    3     |     3      |   0
        //    1     |     2      |   1
        //    1     |     3      |   2
        //    2     |     3      |   1
        $this->assertCount(6, $results);

        // quirky depth count test
        $depthTest = array();

        foreach ($results as $result)
        {
            switch($result['depth'])
            {
                case 0:
                    $depthTest[0][] = $result;
                    $this->assertEquals($result['ancestor'], $result['descendant']);
                break;
                case 1:
                    $depthTest[1][] = $result;
                break;
                case 2:
                    $depthTest[2][] = $result;
                break;
            }
        }

        // we must have 3 nodes with depth = 0
        //              2 nodes with depth = 1
        //              1 node with depth  = 2
        $this->assertCount(3, $depthTest[0]);
        $this->assertCount(2, $depthTest[1]);
        $this->assertCount(1, $depthTest[2]);

        foreach ($depthTest[1] as $dt)
        {
            switch($dt['descendant'])
            {
                case $child->id:
                    $this->assertEquals($page->id, $dt['ancestor']);
                    break;
                case $grandchild->id:
                    $this->assertEquals($child->id, $dt['ancestor']);
                    break;
            }
        }

        $this->assertEquals($page->id, $depthTest[2][0]['ancestor']);
    }

    /**
     * Tests removing a child Entity with given position
     *
     * @return void
     */
    public function testRemoveChild()
    {
        $page = new Page(array(
            'title' => 'Test Title',
            'excerpt' => 'Test Excerpt',
            'content' => 'Test content'
        ));

        $page->save();

        $child = new Page(array(
            'title' => 'Child Page Test Title',
            'excerpt' => 'Child Page Test Excerpt',
            'content' => 'Child Page Test content'
        ));

        $child = $page->appendChild($child, 0, true);

        $this->assertTrue($child->exists);
        $this->assertEquals($page, $child->getParent());

        $page->removeChild();

        $this->assertEquals(1, Page::count());
        $this->assertEquals(1, ClosureTable::count());

        //
        // now we will test removing node from the certain position
        //

        $page->appendChild(new Page(array(
            'title' => 'Child Page Test Title',
            'excerpt' => 'Child Page Test Excerpt',
            'content' => 'Child Page Test content'
        )), 0)->appendChild(new Page(array(
            'title' => 'Child Page Test Title',
            'excerpt' => 'Child Page Test Excerpt',
            'content' => 'Child Page Test content'
        )), 1)->appendChild(new Page(array(
            'title' => 'Child Page Test Title',
            'excerpt' => 'Child Page Test Excerpt',
            'content' => 'Child Page Test content'
        )), 2);

        $this->assertEquals(4, Page::count());
        $this->assertEquals(7, ClosureTable::count());

        $page->removeChild(1);

        $this->assertEquals(3, Page::count());
        $this->assertEquals(5, ClosureTable::count());

        $positions = Page::all()->lists('position');
        $this->assertNotContains(1, $positions);
    }
}