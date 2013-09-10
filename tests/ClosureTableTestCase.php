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

    protected function prepareTestedEntity()
    {
        $page = new Page(array(
            'title' => 'Test Title',
            'excerpt' => 'Test Excerpt',
            'content' => 'Test content'
        ));

        $page->save();

        return $page;
    }

    protected function prepareTestedRelationships()
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

        $grandchild = new Page(array(
            'title' => 'GrandChild Page Test Title',
            'excerpt' => 'GrandChild Page Test Excerpt',
            'content' => 'GrandChild Page Test content'
        ));

        $grandchild = $child->appendChild($grandchild, 0, true);

        return array($page, $child, $grandchild);
    }

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
        $resultsql = strtolower($result->toSql());
        $rightsql = 'select "pages".* from "pages" inner join "pages_closure" on "pages_closure"."ancestor" = "pages"."id" where "pages_closure"."descendant" is null and "pages_closure"."depth" > ?';

        $this->assertEquals($rightsql, $resultsql);
    }

    public function testAncestors()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();
        $ancestors = $grandchild->ancestors();

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $ancestors);
        $this->assertCount(2, $ancestors);
        $this->assertEquals($page->id, $child->getParent()->id);
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

    public function testBuildChildrenQuery()
    {
        $page = $this->prepareTestedEntity();
        $reflection = new ReflectionClass('Page');
        $method = $reflection->getMethod('buildChildrenQuery');
        $method->setAccessible(true);
        $result = $method->invoke($page);

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Builder', $result);

        $resultsql = strtolower($result->toSql());
        $rightsql = 'select * from "pages" inner join "pages_closure" on "pages_closure"."descendant" = "pages"."id" where "pages_closure"."ancestor" = ? and "pages_closure"."depth" = ?';

        $this->assertEquals($rightsql, $resultsql);
    }

    public function testChildren()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();
        $pageChildren = $page->children();

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $pageChildren);
        $this->assertCount(1, $pageChildren);
    }

    public function testHasChildren()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();
        $hasChildren = $page->hasChildren();

        $this->assertInternalType('bool', $hasChildren);
        $this->assertTrue($hasChildren);
    }

    public function testCountChildren()
    {
        list($page, $child, $grandchild) = $this->prepareTestedRelationships();
        $number = $page->countChildren();

        $this->assertInternalType('integer', $number);
        $this->assertEquals(1, $number);
    }

    /*public function testBuildDescendantsQuery()
    {
        $page = $this->prepareTestedEntity();
        $reflection = new ReflectionClass('Page');
        $method = $reflection->getMethod('buildDescendantsQuery');
        $method->setAccessible(true);
        $result = $method->invoke($page);

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Builder', $result);

        $resultsql = strtolower($result->toSql());
        $rightsql = 'select * from "pages" inner join "pages_closure" on "pages_closure"."descendant" = "pages"."id" where "pages_closure"."ancestor" = ? and "pages_closure"."depth" = ?';

        $this->assertEquals($rightsql, $resultsql);
    }*/

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
