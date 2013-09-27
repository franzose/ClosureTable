<?php require_once('tests/app.php');

use \Illuminate\Support\Facades\Schema;
use \Illuminate\Support\Facades\DB;
use \Illuminate\Support\Facades\Config;

class ClosureTableTestCase extends \PHPUnit_Framework_TestCase {

    public function setUp()
    {
        parent::setUp();

        // since we use SQLite we have to add foreign keys support manually.
        DB::unprepared('PRAGMA foreign_keys = ON;');
        Schema::create('pages', function($table){
            $table->increments('id')->unsigned();
            $table->string('title', 250);
            $table->string('excerpt', 500);
            $table->text('content');
            $table->string('language', 2);
            $table->integer('position')->unsigned();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pages_closure', function($table){
            $table->increments('ctid');
            $table->integer('ancestor')->unsigned();
            $table->integer('descendant')->unsigned();
            $table->integer('depth')->unsigned();

            $table->foreign('ancestor')->references('id')->on('pages')->onDelete('cascade');
            $table->foreign('descendant')->references('id')->on('pages')->onDelete('cascade');
            $table->index('depth');
        });
    }

    public function tearDown()
    {
        Schema::drop('pages');
        Schema::drop('pages_closure');
    }

    protected function prepareTestedEntity()
    {
        $page = Page::create(array(
            'title' => 'Test Title',
            'excerpt' => 'Test Excerpt',
            'content' => 'Test content'
        ));

        return $page;
    }

    protected function prepareTestedRelationships()
    {
        $page = Page::create(array(
            'title' => 'Test Title',
            'excerpt' => 'Test Excerpt',
            'content' => 'Test content',
            'language' => 'en'
        ));

        $child = $page->appendChild(new Page(array(
            'title' => 'Child Page Test Title',
            'excerpt' => 'Child Page Test Excerpt',
            'content' => 'Child Page Test content',
            'language' => 'en'
        )), 0, true);

        $grandchild = $child->appendChild(new Page(array(
            'title' => 'GrandChild Page Test Title',
            'excerpt' => 'GrandChild Page Test Excerpt',
            'content' => 'GrandChild Page Test content',
            'language' => 'en'
        )), 0, true);

        $newPage = Page::create(array(
            'title' => 'Another Test Title',
            'excerpt' => 'Another Test Excerpt',
            'content' => 'Another Test content',
            'language' => 'ru'
        ));

        $newChild = $newPage->appendChild(new Page(array(
            'title' => 'Another Child Page Test Title',
            'excerpt' => 'Another Child Page Test Excerpt',
            'content' => 'Another Child Page Test content',
            'language' => 'ru'
        )), 0, true);

        $newGrandchild = $newChild->appendChild(new Page(array(
            'title' => 'Another GrandChild Page Test Title',
            'excerpt' => 'Another GrandChild Page Test Excerpt',
            'content' => 'Another GrandChild Page Test content',
            'language' => 'ru'
        )), 0, true);

        return array($page, $child, $grandchild, $newPage, $newChild, $newGrandchild);
    }

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

    public function testNewEntity()
    {
        $page = new Page;
        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $page);
        $this->assertEquals('pages', $page->getTable());
        $this->assertEquals('id', $page->getKeyName());
        $this->assertEquals('pages.id', $page->getQualifiedKeyName());
        $this->assertEquals('pages_closure', $page->getClosure());
    }

    public function testCreate()
    {
        $page = Page::create(array(
            'title' => 'Test Title',
            'excerpt' => 'Test Excerpt',
            'content' => 'Test content'
        ));

        $this->assertEquals(true, $page->exists);
        $this->assertEquals(1, Page::count());
        $this->assertTrue(isset($page->id));
        $this->assertInstanceOf('DateTime', $page->created_at);
        $this->assertEquals('Test Title', $page->title);
        $this->assertEquals('Test Excerpt', $page->excerpt);
        $this->assertEquals('Test content', $page->content);

        $hidden = $page->getHidden();

        $this->assertTrue(is_array($hidden));
        $this->assertTrue(isset($hidden['ancestor']));
        $this->assertTrue(isset($hidden['descendant']));
        $this->assertTrue(isset($hidden['depth']));
        $this->assertEquals(1, $hidden['ancestor']);
        $this->assertEquals(1, $hidden['descendant']);
        $this->assertEquals(0, $hidden['depth']);

        $closure = DB::table($page->getClosure())->get();

        $this->assertEquals(1, count($closure));
        $this->assertEquals($page->id, $closure[0]->ancestor);
        $this->assertEquals($page->id, $closure[0]->descendant);
        $this->assertEquals(0, $closure[0]->depth);
    }

    public function testDelete()
    {
        $page = $this->prepareTestedEntity();
        $pid = $page->id;
        $page->delete();

        $this->assertTrue($page->trashed());

        $page->exists = true;
        $page->restore();
        $page->forceDelete();
        $result = DB::table($page->getClosure())->where('descendant', '=', $pid)->count();

        $this->assertEquals(0, Page::count());
        $this->assertEquals(0, $result);
    }

    public function testDeleteSubtree()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();
        $closure = $page->getClosure();
        $ids = array($page->id, $child->id, $grandchild->id);

        $page->deleteSubtree();
        $results = Page::whereIn('id', $ids)->count();
        $closure = DB::table($closure)->whereIn('descendant', $ids)->count();

        $this->assertEquals(0, $results);
        $this->assertEquals(0, $closure);
    }

    public function testGetClosure()
    {
        $page = new Page;
        $this->assertEquals('pages_closure', $page->getClosure());
    }

    public function testGetClosureAttributes()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();
        $reflection = new ReflectionClass('\Franzose\ClosureTable\Entity');
        $method = $reflection->getMethod('getClosureAttributes');
        $method->setAccessible(true);
        $results = $method->invoke($grandchild);

        $this->assertInternalType('array', $results);
        $this->assertTrue(isset($results['ancestor']));
        $this->assertTrue(isset($results['descendant']));
        $this->assertTrue(isset($results['depth']));
        $this->assertEquals(1, $results['ancestor']);
        $this->assertEquals(3, $results['descendant']);
        $this->assertEquals(2, $results['depth']);
    }

    public function testGetAncestor()
    {
        $page = $this->prepareTestedEntity();
        $reflection = new ReflectionClass('\Franzose\ClosureTable\Entity');
        $method = $reflection->getMethod('getAncestor');
        $method->setAccessible(true);
        $result = $method->invoke($page);

        $this->assertEquals($page->getHidden()['ancestor'], $result);
    }

    public function testGetDescendant()
    {
        $page = $this->prepareTestedEntity();
        $reflection = new ReflectionClass('\Franzose\ClosureTable\Entity');
        $method = $reflection->getMethod('getDescendant');
        $method->setAccessible(true);
        $result = $method->invoke($page);

        $this->assertEquals($page->getHidden()['descendant'], $result);
    }

    public function testGetDepth()
    {
        $page = $this->prepareTestedEntity();
        $this->assertEquals($page->getHidden()['depth'], $page->getDepth());
    }

    public function testParent()
    {
        list($page, $child) = $this->prepareTestedRelationships();
        $parent = $child->parent();

        $this->assertEquals($page->id, $parent->id);
    }

    public function testAncestors()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();
        $ancestors = $grandchild->ancestors(true);

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $ancestors);
        $this->assertCount(2, $ancestors);
        $this->assertEquals($page->id, $child->parent()->id);
        $this->assertEquals($child->id, $grandchild->parent()->id);
    }

    public function testHasAncestors()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();
        $hasAncestors = $grandchild->hasAncestors();

        $this->assertInternalType('bool', $hasAncestors);
        $this->assertTrue($hasAncestors);
    }

    public function testCountAncestors()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();
        $number = $grandchild->countAncestors();

        $this->assertInternalType('integer', $number);
        $this->assertEquals(2, $number);
    }

    public function testChildren()
    {
        list($page) = $this->prepareTestedRelationships();
        $pageChildren = $page->children();

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $pageChildren);
        $this->assertCount(1, $pageChildren);
    }

    public function testHasChildren()
    {
        list($page) = $this->prepareTestedRelationships();
        $hasChildren = $page->hasChildren();

        $this->assertInternalType('bool', $hasChildren);
        $this->assertTrue($hasChildren);
    }

    public function testCountChildren()
    {
        list($page) = $this->prepareTestedRelationships();
        $number = $page->countChildren();

        $this->assertInternalType('integer', $number);
        $this->assertEquals(1, $number);
    }

    public function testFirstChild()
    {
        list($page, $child1, $child2) = $this->prepareTestedSiblings();
        $firstChild = $page->firstChild();

        $this->assertEquals($child1->id, $firstChild->id);
        $this->assertNotNull($firstChild->parent());
        $this->assertEquals($page->id, $firstChild->parent()->id);
    }

    public function testLastChild()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $lastChild = $page->lastChild();

        $this->assertEquals($child4->id, $lastChild->id);
        $this->assertNotNull($lastChild->parent());
        $this->assertEquals($page->id, $lastChild->parent()->id);
    }

    public function testChildAt()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $childAt = $page->childAt(2);

        $this->assertEquals($child3->id, $childAt->id);
        $this->assertNotNull($childAt->parent());
        $this->assertEquals($page->id, $childAt->parent()->id);
    }

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
        $this->assertEquals($page->id, $child->parent()->id);

        $page->removeChild(null, true); //force delete

        $this->assertEquals(1, Page::count());
        $this->assertEquals(1, DB::table($page->getClosure())->count());

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
        $this->assertEquals(7, DB::table($page->getClosure())->count());

        $page->removeChild(1, true);

        $this->assertEquals(3, Page::count());
        $this->assertEquals(5, DB::table($page->getClosure())->count());

        $positions = Page::all()->lists('position');
        $this->assertNotContains(1, $positions);
    }

    public function testDescendants()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();

        $tree = $page->descendants();

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $tree);
        $this->assertCount(1, $tree);
        $this->assertEquals($grandchild->id, $tree[0]->nested[0]->id);
        $this->assertEquals($child->id, $tree[0]->id);

        $descendants = $page->descendants(null, true);

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $descendants);
        $this->assertCount(2, $descendants);
        $this->assertEquals($child->parent()->id, $page->id);
        $this->assertEquals($grandchild->parent()->id, $child->id);
    }

    public function testHasDescendants()
    {
        list($page) = $this->prepareTestedRelationships();
        $hasDescendants = $page->hasDescendants();

        $this->assertInternalType('bool', $hasDescendants);
        $this->assertTrue($hasDescendants);
    }

    public function testCountDescendants()
    {
        list($page) = $this->prepareTestedRelationships();
        $number = $page->countDescendants();

        $this->assertInternalType('integer', $number);
        $this->assertEquals(2, $number);
    }

    public function testSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();

        // next siblings
        $this->assertEquals(0, $child1->position);
        $this->assertEquals(1, $child2->position);
        $this->assertEquals(2, $child3->position);
        $this->assertEquals(3, $child4->position);

        $child1siblings = $child1->siblings('all', 'next');

        $this->assertEquals(3, $child1siblings->count());
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

    public function testPrevSibling()
    {
        list($page, $child1, $child2) = $this->prepareTestedSiblings();
        $prev = $child2->prevSibling();

        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $prev);
        $this->assertEquals($child1->id, $prev->id);
        $this->assertEquals($child1->position, $prev->position);
    }

    public function testPrevSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $prevs = $child4->prevSiblings();

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $prevs);
        $this->assertCount(3, $prevs);
    }

    public function testHasPrevSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $hasPrevs = $child4->hasPrevSiblings();

        $this->assertInternalType('bool', $hasPrevs);
        $this->assertTrue($hasPrevs);
    }

    public function testCountPrevSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $number = $child4->countPrevSiblings();

        $this->assertInternalType('integer', $number);
        $this->assertEquals(3, $number);
    }

    public function testNextSibling()
    {
        list($page, $child1, $child2) = $this->prepareTestedSiblings();
        $next = $child1->nextSibling();

        $this->assertInstanceOf('Franzose\ClosureTable\Entity', $next);
        $this->assertEquals($child2->id, $next->id);
        $this->assertEquals($child2->position, $next->position);
    }

    public function testNextSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $next = $child1->nextSiblings();

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $next);
        $this->assertCount(3, $next);
    }

    public function testHasNextSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $hasNext = $child1->hasNextSiblings();

        $this->assertInternalType('bool', $hasNext);
        $this->assertTrue($hasNext);
    }

    public function testCountNextSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $number = $child1->countNextSiblings();

        $this->assertInternalType('integer', $number);
        $this->assertEquals(3, $number);
    }

    public function testHasSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $has = $child3->hasSiblings();

        $this->assertInternalType('bool', $has);
        $this->assertTrue($has);
    }

    public function testCountSiblings()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $number = $child3->countSiblings();

        $this->assertInternalType('integer', $number);
        $this->assertEquals(3, $number);
    }

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

    public function testIsRoot()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();

        $this->assertFalse($child->isRoot());
        $this->assertFalse($grandchild->isRoot());
        $this->assertTrue($page->isRoot());
    }

    public function testMakeRootOrMoveToNull()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();
        $grandchild->makeRoot();
        $child->moveTo();

        $this->assertFalse($grandchild->hasAncestors());
        $this->assertFalse($child->hasAncestors());

        $grandchildRows = DB::table($page->closure)->where('descendant', '=', $grandchild->id)->where('depth', '>', 0)->count();
        $childRows = DB::table($page->closure)->where('descendant', '=', $child->id)->where('depth', '>', 0)->count();

        $this->assertEquals(0, $grandchildRows);
        $this->assertEquals(0, $childRows);
    }

    public function testTree()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();
        $tree = Page::tree();

        $this->assertNotNull($tree[0]->nested);
        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $tree[0]->nested);
        $this->assertCount(1, $tree[0]->nested);
        $this->assertInstanceOf('\Franzose\ClosureTable\Entity', $tree[0]->nested[0]);
        $this->assertEquals($child->id, $tree[0]->nested[0]->id);
        $this->assertNotNull($tree[0]->nested[0]->firstChild());
        $this->assertEquals($grandchild->id, $tree[0]->nested[0]->firstChild()->id);
    }

    public function testFilteredTree()
    {
        list($page, $child, $grandchild, $newPage, $newChild, $newGrandchild) = $this->prepareTestedRelationships();
        $tree = Page::filteredTree('language','=','ru');

        $this->assertNotNull($tree[0]->nested); 
        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $tree[0]->nested);
        $this->assertCount(1, $tree[0]->nested);
        $this->assertInstanceOf('\Franzose\ClosureTable\Entity', $tree[0]->nested[0]);
        $this->assertEquals($newChild->id, $tree[0]->nested[0]->id);
        $this->assertNotNull($tree[0]->nested[0]->firstChild());
        $this->assertEquals($newGrandchild->id, $tree[0]->nested[0]->firstChild()->id);
    }

    public function testMoveGivenTo()
    {
        list($page, $child1, $child2, $child3, $child4) = $this->prepareTestedSiblings();
        $child2 = Page::moveGivenTo($child2, $child3);

        $this->assertNotNull($child2->parent());
        $this->assertEquals($child3->id, $child2->parent()->id);

        $results = DB::table($page->getClosure())->where('descendant', '=', $child2->id)->count();

        $this->assertEquals(3, $results);
    }

    public function testRelationsSyncOnChildInsert()
    {
        list($page, $child, $grandchild, $newPage, $newChild, $newGrandchild) = $this->prepareTestedRelationships();

        $results = DB::table($page->getClosure())->get();

        // we must have twelve rows in closure table now
        // =============================
        // ancestor | descendant | depth
        //    1     |     1      |   0   
        //    2     |     2      |   0  
        //    3     |     3      |   0   
        //    4     |     4      |   0   
        //    5     |     5      |   0   
        //    6     |     6      |   0   
        //    1     |     2      |   1    
        //    1     |     3      |   2  
        //    2     |     3      |   1   
        //    4     |     5      |   1   
        //    4     |     6      |   2   
        //    5     |     6      |   1   

        $this->assertCount(12, $results);

        // quirky depth count test
        $depthTest = array();

        foreach ($results as $result)
        {
            switch($result->depth)
            {
                case 0:
                    $depthTest[0][] = $result;
                    $this->assertEquals($result->ancestor, $result->descendant);
                break;
                case 1:
                    $depthTest[1][] = $result;
                break;
                case 2:
                    $depthTest[2][] = $result;
                break;
            }
        }

        // we must have 6 nodes with depth = 0
        //              4 nodes with depth = 1
        //              2 node with depth  = 2
        $this->assertCount(6, $depthTest[0]);
        $this->assertCount(4, $depthTest[1]);
        $this->assertCount(2, $depthTest[2]);

        foreach ($depthTest[1] as $dt)
        {
            switch($dt->descendant)
            {
                case $child->id:
                    $this->assertEquals($page->id, $dt->ancestor);
                    break;
                case $grandchild->id:
                    $this->assertEquals($child->id, $dt->ancestor);
                    break;
                case $newChild->id:
                    $this->assertEquals($newPage->id, $dt->ancestor);
                    break;
                case $newGrandchild->id:
                    $this->assertEquals($newChild->id, $dt->ancestor);
                    break;
            }
        }

        $this->assertEquals($page->id, $depthTest[2][0]->ancestor);
    }
}