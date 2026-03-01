<?php

declare(strict_types=1);

namespace Waaseyaa\Menu\Tests\Unit;

use Waaseyaa\Menu\MenuLink;
use Waaseyaa\Menu\MenuTreeBuilder;
use Waaseyaa\Menu\MenuTreeElement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Waaseyaa\Menu\MenuTreeBuilder
 */
final class MenuTreeBuilderTest extends TestCase
{
    private MenuTreeBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new MenuTreeBuilder();
    }

    public function testEmptyList(): void
    {
        $tree = $this->builder->buildTree([]);

        $this->assertSame([], $tree);
    }

    public function testFlatListAllRoots(): void
    {
        $links = [
            new MenuLink(['id' => 1, 'title' => 'Home', 'url' => '/', 'menu_name' => 'main', 'weight' => 0]),
            new MenuLink(['id' => 2, 'title' => 'About', 'url' => '/about', 'menu_name' => 'main', 'weight' => 1]),
            new MenuLink(['id' => 3, 'title' => 'Contact', 'url' => '/contact', 'menu_name' => 'main', 'weight' => 2]),
        ];

        $tree = $this->builder->buildTree($links);

        $this->assertCount(3, $tree);
        $this->assertContainsOnlyInstancesOf(MenuTreeElement::class, $tree);
        $this->assertSame('Home', $tree[0]->link->getTitle());
        $this->assertSame('About', $tree[1]->link->getTitle());
        $this->assertSame('Contact', $tree[2]->link->getTitle());
        $this->assertFalse($tree[0]->hasChildren());
    }

    public function testTwoLevelHierarchy(): void
    {
        $links = [
            new MenuLink(['id' => 1, 'title' => 'Products', 'url' => '/products', 'menu_name' => 'main', 'weight' => 0]),
            new MenuLink(['id' => 2, 'title' => 'Widget A', 'url' => '/products/a', 'menu_name' => 'main', 'weight' => 0, 'parent_id' => 1]),
            new MenuLink(['id' => 3, 'title' => 'Widget B', 'url' => '/products/b', 'menu_name' => 'main', 'weight' => 1, 'parent_id' => 1]),
        ];

        $tree = $this->builder->buildTree($links);

        $this->assertCount(1, $tree);
        $this->assertSame('Products', $tree[0]->link->getTitle());
        $this->assertTrue($tree[0]->hasChildren());
        $this->assertCount(2, $tree[0]->children);
        $this->assertSame('Widget A', $tree[0]->children[0]->link->getTitle());
        $this->assertSame('Widget B', $tree[0]->children[1]->link->getTitle());
    }

    public function testMultiLevelNesting(): void
    {
        $links = [
            new MenuLink(['id' => 1, 'title' => 'Level 0', 'url' => '/l0', 'menu_name' => 'main', 'weight' => 0]),
            new MenuLink(['id' => 2, 'title' => 'Level 1', 'url' => '/l1', 'menu_name' => 'main', 'weight' => 0, 'parent_id' => 1]),
            new MenuLink(['id' => 3, 'title' => 'Level 2', 'url' => '/l2', 'menu_name' => 'main', 'weight' => 0, 'parent_id' => 2]),
        ];

        $tree = $this->builder->buildTree($links);

        $this->assertCount(1, $tree);
        $this->assertSame('Level 0', $tree[0]->link->getTitle());
        $this->assertSame(2, $tree[0]->getDepth());

        $level1 = $tree[0]->children[0];
        $this->assertSame('Level 1', $level1->link->getTitle());
        $this->assertSame(1, $level1->getDepth());

        $level2 = $level1->children[0];
        $this->assertSame('Level 2', $level2->link->getTitle());
        $this->assertSame(0, $level2->getDepth());
    }

    public function testWeightBasedOrdering(): void
    {
        $links = [
            new MenuLink(['id' => 1, 'title' => 'Third', 'url' => '/c', 'menu_name' => 'main', 'weight' => 10]),
            new MenuLink(['id' => 2, 'title' => 'First', 'url' => '/a', 'menu_name' => 'main', 'weight' => -5]),
            new MenuLink(['id' => 3, 'title' => 'Second', 'url' => '/b', 'menu_name' => 'main', 'weight' => 0]),
        ];

        $tree = $this->builder->buildTree($links);

        $this->assertCount(3, $tree);
        $this->assertSame('First', $tree[0]->link->getTitle());
        $this->assertSame('Second', $tree[1]->link->getTitle());
        $this->assertSame('Third', $tree[2]->link->getTitle());
    }

    public function testWeightOrderingWithinChildren(): void
    {
        $links = [
            new MenuLink(['id' => 1, 'title' => 'Parent', 'url' => '/parent', 'menu_name' => 'main', 'weight' => 0]),
            new MenuLink(['id' => 2, 'title' => 'Child B', 'url' => '/b', 'menu_name' => 'main', 'weight' => 5, 'parent_id' => 1]),
            new MenuLink(['id' => 3, 'title' => 'Child A', 'url' => '/a', 'menu_name' => 'main', 'weight' => -1, 'parent_id' => 1]),
        ];

        $tree = $this->builder->buildTree($links);

        $this->assertCount(1, $tree);
        // Children should be sorted by weight
        $this->assertSame('Child A', $tree[0]->children[0]->link->getTitle());
        $this->assertSame('Child B', $tree[0]->children[1]->link->getTitle());
    }

    public function testOrphanLinksAreRoots(): void
    {
        // A link whose parent_id does not exist in the set should become a root.
        $links = [
            new MenuLink(['id' => 10, 'title' => 'Orphan', 'url' => '/orphan', 'menu_name' => 'main', 'weight' => 0, 'parent_id' => 999]),
        ];

        $tree = $this->builder->buildTree($links);

        $this->assertCount(1, $tree);
        $this->assertSame('Orphan', $tree[0]->link->getTitle());
    }

    public function testMixedRootsAndChildren(): void
    {
        $links = [
            new MenuLink(['id' => 1, 'title' => 'Home', 'url' => '/', 'menu_name' => 'main', 'weight' => 0]),
            new MenuLink(['id' => 2, 'title' => 'Products', 'url' => '/products', 'menu_name' => 'main', 'weight' => 1]),
            new MenuLink(['id' => 3, 'title' => 'Widget', 'url' => '/products/widget', 'menu_name' => 'main', 'weight' => 0, 'parent_id' => 2]),
            new MenuLink(['id' => 4, 'title' => 'Contact', 'url' => '/contact', 'menu_name' => 'main', 'weight' => 2]),
        ];

        $tree = $this->builder->buildTree($links);

        $this->assertCount(3, $tree);
        $this->assertSame('Home', $tree[0]->link->getTitle());
        $this->assertFalse($tree[0]->hasChildren());

        $this->assertSame('Products', $tree[1]->link->getTitle());
        $this->assertTrue($tree[1]->hasChildren());
        $this->assertCount(1, $tree[1]->children);
        $this->assertSame('Widget', $tree[1]->children[0]->link->getTitle());

        $this->assertSame('Contact', $tree[2]->link->getTitle());
        $this->assertFalse($tree[2]->hasChildren());
    }

    public function testSingleLink(): void
    {
        $links = [
            new MenuLink(['id' => 1, 'title' => 'Home', 'url' => '/', 'menu_name' => 'main', 'weight' => 0]),
        ];

        $tree = $this->builder->buildTree($links);

        $this->assertCount(1, $tree);
        $this->assertSame('Home', $tree[0]->link->getTitle());
        $this->assertFalse($tree[0]->hasChildren());
    }

    public function testParentIdZeroTreatedAsRoot(): void
    {
        $links = [
            new MenuLink(['id' => 1, 'title' => 'Root', 'url' => '/', 'menu_name' => 'main', 'weight' => 0, 'parent_id' => 0]),
        ];

        $tree = $this->builder->buildTree($links);

        $this->assertCount(1, $tree);
        $this->assertSame('Root', $tree[0]->link->getTitle());
    }
}
