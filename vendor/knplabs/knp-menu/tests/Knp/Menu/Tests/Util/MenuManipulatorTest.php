<?php

namespace Knp\Menu\Tests\Util;

use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use Knp\Menu\Tests\MenuTestCase;
use Knp\Menu\Util\MenuManipulator;

final class MenuManipulatorTest extends MenuTestCase
{
    public function testMoveToFirstPosition()
    {
        $menu = new MenuItem('root', new MenuFactory());
        $menu->addChild('c1');
        $menu->addChild('c2');
        $menu->addChild('c3');
        $menu->addChild('c4');

        $manipulator = new MenuManipulator();
        $manipulator->moveToFirstPosition($menu['c3']);
        $this->assertEquals(['c3', 'c1', 'c2', 'c4'], \array_keys($menu->getChildren()));
    }

    public function testMoveToLastPosition()
    {
        $menu = new MenuItem('root', new MenuFactory());
        $menu->addChild('c1');
        $menu->addChild('c2');
        $menu->addChild('c3');
        $menu->addChild('c4');

        $manipulator = new MenuManipulator();
        $manipulator->moveToLastPosition($menu['c2']);
        $this->assertEquals(['c1', 'c3', 'c4', 'c2'], \array_keys($menu->getChildren()));
    }

    public function testMoveToPosition()
    {
        $menu = new MenuItem('root', new MenuFactory());
        $menu->addChild('c1');
        $menu->addChild('c2');
        $menu->addChild('c3');
        $menu->addChild('c4');

        $manipulator = new MenuManipulator();
        $manipulator->moveToPosition($menu['c1'], 2);
        $this->assertEquals(['c2', 'c3', 'c1', 'c4'], \array_keys($menu->getChildren()));
    }

    /**
     * @dataProvider getSliceData
     */
    public function testSlice($offset, $length, $count, $keys)
    {
        $manipulator = new MenuManipulator();
        $sliced = $manipulator->slice($this->pt1, $offset, $length);
        $this->assertCount($count, $sliced);
        $this->assertEquals($keys, \array_keys($sliced->getChildren()));
    }

    public function getSliceData()
    {
        $this->setUp();

        return [
            'numeric offset and numeric length' => [0, 2, 2, [$this->ch1->getName(), $this->ch2->getName()]],
            'numeric offset and no length' => [0, null, 3, [$this->ch1->getName(), $this->ch2->getName(), $this->ch3->getName()]],
            'named offset and no length' => ['Child 2', null, 2, [$this->ch2->getName(), $this->ch3->getName()]],
            'child offset and no length' => [$this->ch3, null, 1, [$this->ch3->getName()]],
            'numeric offset and named length' => [0, 'Child 3', 2, [$this->ch1->getName(), $this->ch2->getName()]],
            'numeric offset and child length' => [0, $this->ch3, 2, [$this->ch1->getName(), $this->ch2->getName()]],
        ];
    }

    /**
     * @dataProvider getSplitData
     */
    public function testSplit($length, $count, $keys)
    {
        $manipulator = new MenuManipulator();
        $splitted = $manipulator->split($this->pt1, $length);
        $this->assertArrayHasKey('primary', $splitted);
        $this->assertArrayHasKey('secondary', $splitted);
        $this->assertCount($count, $splitted['primary']);
        $this->assertCount(3 - $count, $splitted['secondary']);
        $this->assertEquals($keys, \array_keys($splitted['primary']->getChildren()));
    }

    public function getSplitData()
    {
        $this->setUp();

        return [
            'numeric length' => [1, 1, [$this->ch1->getName()]],
            'named length' => ['Child 3', 2, [$this->ch1->getName(), $this->ch2->getName()]],
            'child length' => [$this->ch3, 2, [$this->ch1->getName(), $this->ch2->getName()]],
        ];
    }

    public function testPathAsString()
    {
        $manipulator = new MenuManipulator();
        $this->assertEquals('Root li > Parent 2 > Child 4', $manipulator->getPathAsString($this->ch4), 'Path with default separator');
        $this->assertEquals('Root li / Parent 1 / Child 2', $manipulator->getPathAsString($this->ch2, ' / '), 'Path with custom separator');
    }

    public function testBreadcrumbsArray()
    {
        $manipulator = new MenuManipulator();
        $this->menu->addChild('child', ['uri' => 'http://www.symfony-reloaded.org']);
        $this->menu->addChild('123', ['uri' => 'http://www.symfony-reloaded.org']);

        $this->assertEquals(
            [['label' => 'Root li', 'uri' => null, 'item' => $this->menu], ['label' => 'Parent 1', 'uri' => null, 'item' => $this->pt1]],
            $manipulator->getBreadcrumbsArray($this->pt1)
        );
        $this->assertEquals(
            [['label' => 'Root li', 'uri' => null, 'item' => $this->menu], ['label' => 'child', 'uri' => 'http://www.symfony-reloaded.org', 'item' => $this->menu['child']]],
            $manipulator->getBreadcrumbsArray($this->menu['child'])
        );
        $this->assertEquals(
            [
                ['label' => 'Root li', 'uri' => null, 'item' => $this->menu],
                ['label' => 'child', 'uri' => 'http://www.symfony-reloaded.org', 'item' => $this->menu['child']],
                ['label' => 'subitem1', 'uri' => null, 'item' => null],
            ],
            $manipulator->getBreadcrumbsArray($this->menu['child'], 'subitem1')
        );

        $item = $this->getMockBuilder('Knp\Menu\ItemInterface')->getMock();
        $item->expects($this->any())
            ->method('getLabel')
            ->will($this->returnValue('mock'));
        $item->expects($this->any())
            ->method('getUri')
            ->will($this->returnValue('foo'));

        $this->assertEquals(
            [
                ['label' => 'Root li', 'uri' => null, 'item' => $this->menu],
                ['label' => 'child', 'uri' => 'http://www.symfony-reloaded.org', 'item' => $this->menu['child']],
                ['label' => 'subitem1', 'uri' => null, 'item' => null],
                ['label' => 'subitem2', 'uri' => null, 'item' => null],
                ['label' => 'subitem3', 'uri' => 'http://php.net', 'item' => null],
                ['label' => 'subitem4', 'uri' => null, 'item' => null],
                ['label' => 'mock', 'uri' => 'foo', 'item' => $item],
            ],
            $manipulator->getBreadcrumbsArray($this->menu['child'], [
                'subitem1',
                'subitem2' => null,
                'subitem3' => 'http://php.net',
                ['label' => 'subitem4', 'uri' => null, 'item' => null],
                $item,
            ])
        );

        $this->assertEquals(
            [['label' => 'Root li', 'uri' => null, 'item' => $this->menu], ['label' => '123', 'uri' => 'http://www.symfony-reloaded.org', 'item' => $this->menu['123']]],
            $manipulator->getBreadcrumbsArray($this->menu['123'])
        );

        $this->assertEquals(
            [
                ['label' => 'Root li', 'uri' => null, 'item' => $this->menu],
                ['label' => 'child', 'uri' => 'http://www.symfony-reloaded.org', 'item' => $this->menu['child']],
                ['label' => 'mock', 'uri' => 'foo', 'item' => $item],
            ],
            $manipulator->getBreadcrumbsArray($this->menu['child'], $item)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBreadcrumbsArrayInvalidData()
    {
        $manipulator = new MenuManipulator();
        $manipulator->getBreadcrumbsArray($this->pt1, [new \stdClass()]);
    }

    public function testCallRecursively()
    {
        $factory = new MenuFactory();

        $menu = $factory->createItem('test_menu');

        foreach (range(1, 2) as $i) {
            $child = $this->getMockBuilder('Knp\Menu\ItemInterface')->getMock();
            $child->expects($this->any())
                ->method('getName')
                ->will($this->returnValue('Child '.$i))
            ;
            $child->expects($this->once())
                ->method('setDisplay')
                ->with(false)
            ;
            $child->expects($this->once())
                ->method('getChildren')
                ->will($this->returnValue([]))
            ;
            $menu->addChild($child);
        }

        $manipulator = new MenuManipulator();

        $manipulator->callRecursively($menu, 'setDisplay', [false]);
        $this->assertFalse($menu->isDisplayed());
    }

    public function testToArrayWithChildren()
    {
        $menu = $this->createMenu();
        $menu->addChild('jack', ['uri' => 'http://php.net', 'linkAttributes' => ['title' => 'php'], 'display' => false])
            ->addChild('john', ['current' => true])->setCurrent(true)
        ;
        $menu->addChild('joe', [
            'attributes' => ['class' => 'leaf'],
            'label' => 'test',
            'labelAttributes' => ['class' => 'center'],
            'displayChildren' => false,
        ])->setCurrent(false);

        $manipulator = new MenuManipulator();

        $this->assertEquals(
            [
                'name' => 'test_menu',
                'label' => 'test_menu',
                'uri' => 'homepage',
                'attributes' => [],
                'labelAttributes' => [],
                'linkAttributes' => [],
                'childrenAttributes' => [],
                'extras' => [],
                'display' => true,
                'displayChildren' => true,
                'current' => null,
                'children' => [
                    'jack' => [
                        'name' => 'jack',
                        'label' => 'jack',
                        'uri' => 'http://php.net',
                        'attributes' => [],
                        'labelAttributes' => [],
                        'linkAttributes' => ['title' => 'php'],
                        'childrenAttributes' => [],
                        'extras' => [],
                        'display' => false,
                        'displayChildren' => true,
                        'current' => null,
                        'children' => [
                            'john' => [
                                'name' => 'john',
                                'label' => 'john',
                                'uri' => null,
                                'attributes' => [],
                                'labelAttributes' => [],
                                'linkAttributes' => [],
                                'childrenAttributes' => [],
                                'extras' => [],
                                'display' => true,
                                'displayChildren' => true,
                                'children' => [],
                                'current' => true,
                            ],
                        ],
                    ],
                    'joe' => [
                        'name' => 'joe',
                        'label' => 'test',
                        'uri' => null,
                        'attributes' => ['class' => 'leaf'],
                        'labelAttributes' => ['class' => 'center'],
                        'linkAttributes' => [],
                        'childrenAttributes' => [],
                        'extras' => [],
                        'display' => true,
                        'displayChildren' => false,
                        'children' => [],
                        'current' => false,
                    ],
                ],
            ],
            $manipulator->toArray($menu)
        );
    }

    public function testToArrayWithLimitedChildren()
    {
        $menu = $this->createMenu();
        $menu->addChild('jack', ['uri' => 'http://php.net', 'linkAttributes' => ['title' => 'php'], 'display' => false])
            ->addChild('john')
        ;
        $menu->addChild('joe', ['attributes' => ['class' => 'leaf'], 'label' => 'test', 'labelAttributes' => ['class' => 'center'], 'displayChildren' => false]);

        $manipulator = new MenuManipulator();

        $this->assertEquals(
            [
                'name' => 'test_menu',
                'label' => 'test_menu',
                'uri' => 'homepage',
                'attributes' => [],
                'labelAttributes' => [],
                'linkAttributes' => [],
                'childrenAttributes' => [],
                'extras' => [],
                'display' => true,
                'displayChildren' => true,
                'current' => null,
                'children' => [
                    'jack' => [
                        'name' => 'jack',
                        'label' => 'jack',
                        'uri' => 'http://php.net',
                        'attributes' => [],
                        'labelAttributes' => [],
                        'linkAttributes' => ['title' => 'php'],
                        'childrenAttributes' => [],
                        'extras' => [],
                        'display' => false,
                        'displayChildren' => true,
                        'current' => null,
                    ],
                    'joe' => [
                        'name' => 'joe',
                        'label' => 'test',
                        'uri' => null,
                        'attributes' => ['class' => 'leaf'],
                        'labelAttributes' => ['class' => 'center'],
                        'linkAttributes' => [],
                        'childrenAttributes' => [],
                        'extras' => [],
                        'display' => true,
                        'displayChildren' => false,
                        'current' => null,
                    ],
                ],
            ],
            $manipulator->toArray($menu, 1)
        );
    }

    public function testToArrayWithoutChildren()
    {
        $menu = $this->createMenu();
        $menu->addChild('jack', ['uri' => 'http://php.net', 'linkAttributes' => ['title' => 'php'], 'display' => false]);
        $menu->addChild('joe', ['attributes' => ['class' => 'leaf'], 'label' => 'test', 'labelAttributes' => ['class' => 'center'], 'displayChildren' => false]);

        $manipulator = new MenuManipulator();

        $this->assertEquals(
            [
                'name' => 'test_menu',
                'label' => 'test_menu',
                'uri' => 'homepage',
                'attributes' => [],
                'labelAttributes' => [],
                'linkAttributes' => [],
                'childrenAttributes' => [],
                'extras' => [],
                'display' => true,
                'displayChildren' => true,
                'current' => null,
            ],
            $manipulator->toArray($menu, 0)
        );
    }

    /**
     * Create a new MenuItem
     *
     * @param string $name
     * @param string $uri
     * @param array  $attributes
     *
     * @return \Knp\Menu\MenuItem
     */
    private function createMenu($name = 'test_menu', $uri = 'homepage', array $attributes = [])
    {
        $factory = new MenuFactory();

        return $factory->createItem($name, ['attributes' => $attributes, 'uri' => $uri]);
    }
}
