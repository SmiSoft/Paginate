<?php

namespace PHPixie\Tests\Paginate;

/**
 * @coversDefaultClass PHPixie\Paginate\Pager
 */
class PagerTest extends \PHPixie\Test\Testcase
{
    protected $loader;
    protected $pageSize = 5;

    protected $pager;

    public function setUp()
    {
        $this->loader = $this->quickMock('\PHPixie\Paginate\Loader');

        $this->pager = $this->pager();
    }

    /**
     * @covers ::__construct
     * @covers ::<protected>
     */
    public function testConstruct()
    {

    }

    /**
     * @covers ::pageSize
     * @covers ::<protected>
     */
    public function testPageSize()
    {
        $this->assertSame($this->pageSize, $this->pager->pageSize());
    }

    /**
     * @covers ::itemCount
     * @covers ::pageCount
     * @covers ::<protected>
     */
    public function testCount()
    {
        $this->prepareRequireCount(17);
        for($i=0; $i<2; $i++) {
            $this->assertSame(17, $this->pager->itemCount());
            $this->assertSame(4, $this->pager->pageCount());
        }

        $this->pager = $this->pager();
        $this->prepareRequireCount(0);

        for($i=0; $i<2; $i++) {
            $this->assertSame(0, $this->pager->itemCount());
            $this->assertSame(1, $this->pager->pageCount());
        }
    }

    /**
     * @covers ::pageExists
     * @covers ::<protected>
     */
    public function testPageExists()
    {
        $this->prepareRequireCount(17);

        for($i=1; $i<=4; $i++) {
            $this->assertTrue($this->pager->pageExists($i));
        }

        foreach(array(-1, 0, 5) as $page) {
            $this->assertFalse($this->pager->pageExists($page));
        }
    }

    /**
     * @covers ::currentPage
     * @covers ::setCurrentPage
     * @covers ::<protected>
     */
    public function testCurrentPage()
    {
        $this->prepareRequireCount(17);

        $this->assertSame(1, $this->pager->currentPage());

        $this->pager->setCurrentPage(2);
        $this->assertSame(2, $this->pager->currentPage());

        $pager = $this->pager;
        $this->assertException(function() use($pager) {
            $pager->setCurrentPage(5);
        }, '\PHPixie\Paginate\Exception');
    }

    /**
     * @covers ::pageOffsetExists
     * @covers ::getPageByOffset
     * @covers ::<protected>
     */
    public function testPageOffsetExists()
    {
        $this->prepareRequireCount(17);
        $this->pager->setCurrentPage(3);

        foreach(array(-1, 1) as $offset) {
            $this->assertTrue($this->pager->pageOffsetExists($offset));
            $this->assertSame(3+$offset, $this->pager->getPageByOffset($offset));
        }

        foreach(array(-5, 5) as $offset) {
            $this->assertFalse($this->pager->pageOffsetExists($offset));
            $this->assertSame(null, $this->pager->getPageByOffset($offset));
        }
    }

    /**
     * @covers ::nextPage
     * @covers ::previousPage
     * @covers ::<protected>
     */
    public function testPreviousNextPage()
    {
        $this->prepareRequireCount(3*$this->pageSize);

        $map = array(
            1 => array(null, 2),
            2 => array(1, 3),
            3 => array(2, null),
        );

        foreach($map as $page => $expect) {
            $this->pager->setCurrentPage($page);
            $this->assertSame($expect[0], $this->pager->previousPage());
            $this->assertSame($expect[1], $this->pager->nextPage());
        }
    }

    /**
     * @covers ::getAdjacentPages
     * @covers ::<protected>
     */
    public function testGetAdjacentPages()
    {
        $this->prepareRequireCount(8*$this->pageSize);
        $this->assertAdjacentPages(3, array(
            3 => range(2, 4),
            8 => range(1, 8),
            9 => range(1, 8),
            0 => array()
        ));

        $this->assertAdjacentPages(2, array(
            6 => range(1, 6)
        ));

        $this->assertAdjacentPages(7, array(
            6 => range(3, 8)
        ));

        $this->pager = $this->pager();

        $this->prepareRequireCount(0);
        $this->assertAdjacentPages(1, array(
            6 => range(1, 1)
        ));
    }

    protected function assertAdjacentPages($currentPage, $map)
    {
        $this->pager->setCurrentPage($currentPage);
        foreach($map as $limit => $expect) {
            $this->assertSame($expect, $this->pager->getAdjacentPages($limit));
        }
    }

    /**
     * @covers ::getCurrentItems
     * @covers ::<protected>
     */
    public function testCurrentItems()
    {
        $this->currentItemsTest(false);
        $this->currentItemsTest(true);
    }

    protected function currentItemsTest($isLastPage = false)
    {
        $this->pager = $this->pager();

        $itemCount = 6*$this->pageSize - 3;
        $this->prepareRequireCount($itemCount);

        $currentPage = $isLastPage ? 6 : 3;
        $this->pager->setCurrentPage($currentPage);

        $offset = ($currentPage - 1)*$this->pageSize;
        $limit  = $isLastPage ? 2 : $this->pageSize;

        $result = $this->quickMock('\IteratorAggregate');
        $this->method($this->loader, 'getItems', $result, array($offset, $limit), 0);

        $this->assertSame($result, $this->pager->getCurrentItems());
    }

    protected function prepareRequireCount($itemCount, $loaderAt = 0)
    {
        $this->method($this->loader, 'getCount', $itemCount, array(), $loaderAt);
    }

    protected function pager()
    {
        return new \PHPixie\Paginate\Pager(
            $this->loader,
            $this->pageSize
        );
    }

    /**
     * @covers ::render
     */
    public function testRenderTwoItems()
    {
        // test rendering of two items using default scheme
        $this->prepareRequireCount(7);
        $pager=$this->pager;
        // test rendering of first page
        $pager->setCurrentPage(1);
        $render=$pager->render();
        $this->assertSame('1&nbsp;<a href="?page=2">2</a>',$render,'test rendering of first page');
        // test rendering of second page
        $pager->setCurrentPage(2);
        $render=$pager->render();
        $this->assertSame('<a href="?">1</a>&nbsp;2',$render,'test rendering of second page');
    }

    /**
     * @covers ::render
     */
    public function testRenderThreeItems()
    {
        // test rendering of three items using human-readable URL
        // up to three items, default paginator don't create "move to the first", "move to the last", "move next", "move previous"
        $this->prepareRequireCount(14);
        $pager=$this->pager;
        // test rendering of first page
        $pager->setCurrentPage(1);
        $render=$pager->render(array('url'=>'/news/%d'));
        $this->assertSame('1&nbsp;<a href="/news/2">2</a>&nbsp;<a href="/news/3">3</a>',$render,'test rendering of first page');
        // test rendering of second page
        $pager->setCurrentPage(2);
        $render=$pager->render(array('url'=>'/news/%d'));
        $this->assertSame('<a href="/news">1</a>&nbsp;2&nbsp;<a href="/news/3">3</a>',$render,'test rendering of second page');
        // last page has nothing specific to test
    }

    /**
     * @covers ::render
     */
    public function testRenderManyItems()
    {
        // test rendering of five items, like an example of paginator, containing "move to the first" and so on links
        $this->prepareRequireCount(23);
        $pager=$this->pager;
        // test rendering of first page
        $pager->setCurrentPage(1);
        $render=$pager->render(array('url'=>'/pony/%d'));
        $this->assertSame('1&nbsp;<a href="/pony/2">2</a>&nbsp;<a href="/pony/3">3</a>&nbsp;<a href="/pony/4">4</a>&nbsp;<a href="/pony/5">5</a>&nbsp;<a href="/pony/2">&gt;&gt;</a>&nbsp;<a href="/pony/5">&gt;&gt;&gt;</a>',$render,'test rendering of first page');
        // test rendering of second page
        $pager->setCurrentPage(2);
        $render=$pager->render(array('url'=>'/pony/%d'));
        $this->assertSame('<a href="/pony">&lt;&lt;&lt;</a>&nbsp;<a href="/pony">&lt;&lt;</a>&nbsp;<a href="/pony">1</a>&nbsp;2&nbsp;<a href="/pony/3">3</a>&nbsp;<a href="/pony/4">4</a>&nbsp;<a href="/pony/5">5</a>&nbsp;<a href="/pony/3">&gt;&gt;</a>&nbsp;<a href="/pony/5">&gt;&gt;&gt;</a>',$render,'test rendering of second page');
        // test rendering of last page
        $pager->setCurrentPage(5);
        $render=$pager->render(array('url'=>'/pony/%d'));
        $this->assertSame('<a href="/pony">&lt;&lt;&lt;</a>&nbsp;<a href="/pony/4">&lt;&lt;</a>&nbsp;<a href="/pony">1</a>&nbsp;<a href="/pony/2">2</a>&nbsp;<a href="/pony/3">3</a>&nbsp;<a href="/pony/4">4</a>&nbsp;5',$render,'test rendering of last page');
    }

    /**
     * @covers ::render
     */
    public function testSkip()
    {
        // test rendering, that skips some elements
        $this->prepareRequireCount(24);
        $pager=$this->pager;
        // test rendering first element, skipping some elements + non-standart URL
        $pager->setCurrentPage(1);
        $render=$pager->render(array('url'=>'/users?index=%d','urlFirst'=>'/users','around'=>1));
        $this->assertSame('1&nbsp;<a href="/users?index=2">2</a>&hellip;&nbsp;<a href="/users?index=2">&gt;&gt;</a>&nbsp;<a href="/users?index=5">&gt;&gt;&gt;</a>',$render,'test rendering first element, skipping some elements');
        // test rendering center element skipping some elements + non-standart URL
        $pager->setCurrentPage(3);
        $render=$pager->render(array('url'=>'/users?index=%d','urlFirst'=>'/users','around'=>1));
        $this->assertSame('<a href="/users">&lt;&lt;&lt;</a>&nbsp;<a href="/users?index=2">&lt;&lt;</a>&nbsp;&hellip;&nbsp;<a href="/users?index=2">2</a>&nbsp;3&nbsp;<a href="/users?index=4">4</a>&hellip;&nbsp;<a href="/users?index=4">&gt;&gt;</a>&nbsp;<a href="/users?index=5">&gt;&gt;&gt;</a>',$render,'test rendering center element skipping some elements');
        // test rendering last element, skipping some elements + non-standart URL
        $pager->setCurrentPage(5);
        $render=$pager->render(array('url'=>'/users?index=%d','urlFirst'=>'/users','around'=>1));
        $this->assertSame('<a href="/users">&lt;&lt;&lt;</a>&nbsp;<a href="/users?index=4">&lt;&lt;</a>&nbsp;&hellip;&nbsp;<a href="/users?index=4">4</a>&nbsp;5',$render,'test rendering center element skipping some elements');
    }
}
