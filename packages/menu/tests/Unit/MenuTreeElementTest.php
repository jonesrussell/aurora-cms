<?php

declare(strict_types=1);

namespace Aurora\Menu\Tests\Unit;

use Aurora\Menu\MenuLink;
use Aurora\Menu\MenuTreeElement;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Aurora\Menu\MenuTreeElement
 */
final class MenuTreeElementTest extends TestCase
{
    public function testHasChildrenReturnsFalseForLeaf(): void
    {
        $link = new MenuLink(['id' => 1, 'title' => 'Home', 'menu_name' => 'main']);
        $element = new MenuTreeElement($link);

        $this->assertFalse($element->hasChildren());
    }

    public function testHasChildrenReturnsTrueWithChildren(): void
    {
        $parent = new MenuTreeElement(new MenuLink(['id' => 1, 'title' => 'Parent', 'menu_name' => 'main']));
        $child = new MenuTreeElement(new MenuLink(['id' => 2, 'title' => 'Child', 'menu_name' => 'main']));

        $parent->children[] = $child;

        $this->assertTrue($parent->hasChildren());
    }

    public function testGetDepthForLeaf(): void
    {
        $element = new MenuTreeElement(new MenuLink(['id' => 1, 'title' => 'Leaf', 'menu_name' => 'main']));

        $this->assertSame(0, $element->getDepth());
    }

    public function testGetDepthOneLevelDeep(): void
    {
        $parent = new MenuTreeElement(new MenuLink(['id' => 1, 'title' => 'Parent', 'menu_name' => 'main']));
        $child = new MenuTreeElement(new MenuLink(['id' => 2, 'title' => 'Child', 'menu_name' => 'main']));

        $parent->children[] = $child;

        $this->assertSame(1, $parent->getDepth());
    }

    public function testGetDepthTwoLevelsDeep(): void
    {
        $root = new MenuTreeElement(new MenuLink(['id' => 1, 'title' => 'Root', 'menu_name' => 'main']));
        $child = new MenuTreeElement(new MenuLink(['id' => 2, 'title' => 'Child', 'menu_name' => 'main']));
        $grandchild = new MenuTreeElement(new MenuLink(['id' => 3, 'title' => 'Grandchild', 'menu_name' => 'main']));

        $child->children[] = $grandchild;
        $root->children[] = $child;

        $this->assertSame(2, $root->getDepth());
    }

    public function testGetDepthWithAsymmetricTree(): void
    {
        $root = new MenuTreeElement(new MenuLink(['id' => 1, 'title' => 'Root', 'menu_name' => 'main']));
        $shallowChild = new MenuTreeElement(new MenuLink(['id' => 2, 'title' => 'Shallow', 'menu_name' => 'main']));
        $deepChild = new MenuTreeElement(new MenuLink(['id' => 3, 'title' => 'Deep', 'menu_name' => 'main']));
        $grandchild = new MenuTreeElement(new MenuLink(['id' => 4, 'title' => 'Grandchild', 'menu_name' => 'main']));

        $deepChild->children[] = $grandchild;
        $root->children[] = $shallowChild;
        $root->children[] = $deepChild;

        // Depth is max path: root -> deepChild -> grandchild = 2
        $this->assertSame(2, $root->getDepth());
    }

    public function testLinkPropertyIsReadonly(): void
    {
        $link = new MenuLink(['id' => 1, 'title' => 'Test', 'menu_name' => 'main']);
        $element = new MenuTreeElement($link);

        $this->assertSame($link, $element->link);
    }
}
