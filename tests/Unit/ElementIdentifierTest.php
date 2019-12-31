<?php

declare(strict_types=1);

namespace webignition\DomElementIdentifier\Tests\Unit;

use webignition\DomElementIdentifier\ElementIdentifier;
use webignition\DomElementIdentifier\ElementIdentifierInterface;
use webignition\DomElementIdentifier\Serializer;

class ElementIdentifierTest extends \PHPUnit\Framework\TestCase
{
    public function testParentIdentifier()
    {
        $identifier = new ElementIdentifier('.selector');
        $this->assertNull($identifier->getParentIdentifier());

        $parentIdentifier = new ElementIdentifier('.parent');
        $identifier = $identifier->withParentIdentifier($parentIdentifier);

        $this->assertSame($parentIdentifier, $identifier->getParentIdentifier());
    }

    /**
     * @dataProvider getScopeDataProvider
     *
     * @param ElementIdentifierInterface $elementIdentifier
     * @param array<int, ElementIdentifierInterface> $expectedScope
     */
    public function testGetScope(ElementIdentifierInterface $elementIdentifier, array $expectedScope)
    {
        $this->assertEquals($expectedScope, $elementIdentifier->getScope());
    }

    public function getScopeDataProvider(): array
    {
        return [
            'no scope' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'expectedScope' => [],
            ],
            'parent > child' => [
                'elementIdentifier' => (new ElementIdentifier('.child'))
                    ->withParentIdentifier(
                        new ElementIdentifier('.parent')
                    ),
                'expectedScope' => [
                    new ElementIdentifier('.parent'),
                ],
            ],
            'grandparent > parent > child' => [
                'elementIdentifier' => (new ElementIdentifier('.child'))
                    ->withParentIdentifier(
                        (new ElementIdentifier('.parent'))
                            ->withParentIdentifier(
                                new ElementIdentifier('.grandparent')
                            )
                    ),
                'expectedScope' => [
                    new ElementIdentifier('.grandparent'),
                    (new ElementIdentifier('.parent'))
                        ->withParentIdentifier(
                            new ElementIdentifier('.grandparent')
                        ),
                ],
            ],
        ];
    }

    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(ElementIdentifierInterface $domIdentifier, string $expectedString)
    {
        $this->assertSame($expectedString, (string) $domIdentifier);
    }

    public function toStringDataProvider(): array
    {
        return [
            'empty' => [
                'domIdentifier' => new ElementIdentifier(''),
                'expectedString' => '$""',
            ],
            'css selector' => [
                'locator' => new ElementIdentifier('.selector'),
                'expectedString' => '$".selector"',
            ],
            'css selector containing double quotes' => [
                'locator' => new ElementIdentifier('a[href="https://example.org"]'),
                'expectedString' => '$"a[href=\"https://example.org\"]"',
            ],
            'xpath expression' => [
                'locator' => new ElementIdentifier('//body'),
                'expectedString' => '$"//body"',
            ],
            'xpath expression containing double quotes' => [
                'locator' => new ElementIdentifier('//*[@id="id"]'),
                'expectedString' => '$"//*[@id=\"id\"]"',
            ],
            'css selector with ordinal position' => [
                'locator' => new ElementIdentifier('.selector', 3),
                'expectedString' => '$".selector":3',
            ],
            'css selector with parent' => [
                'locator' => (new ElementIdentifier('.selector'))
                    ->withParentIdentifier(
                        new ElementIdentifier('.parent')
                    ),
                'expectedString' => '{{ $".parent" }} $".selector"',
            ],
        ];
    }

    public function testJsonSerialize()
    {
        $this->assertSame(
            [
                Serializer::KEY_LOCATOR => '.selector',
            ],
            (new ElementIdentifier('.selector'))->jsonSerialize()
        );
    }

    public function testFromJson()
    {
        $this->assertEquals(
            new ElementIdentifier('.selector'),
            ElementIdentifier::fromJson((string) json_encode([
                Serializer::KEY_LOCATOR => '.selector',
            ]))
        );
    }
}
