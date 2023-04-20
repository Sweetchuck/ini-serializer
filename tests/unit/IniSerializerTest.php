<?php

declare(strict_types = 1);

namespace Sweetchuck\IniSerializer\Tests\Unit;

use Sweetchuck\IniSerializer\IniSerializer;

/**
 * @covers \Sweetchuck\IniSerializer\IniSerializer
 */
class IniSerializerTest extends \Codeception\Test\Unit
{
    public function casesParse(): array
    {
        return [
            'empty' => [
                [],
                '',
            ],
            'all-in-one' => [
                [
                    'a' => 'b',
                    'c' => 'd',
                    'e' => 'f',
                    '.g' => [
                        '.h' => -1,
                        'i' => -0.5,
                        'j' => 0,
                        'k' => 0,
                        'l' => 0.5,
                        'm' => 1,
                    ],
                    'n-[-]-n' => [
                        'o' => null,
                        'p' => null,
                        'q' => true,
                        'r' => false,
                        's' => '',
                    ],
                ],
                implode(PHP_EOL, [
                    'a = b',
                    'c= d',
                    'e =f',
                    '[.g]',
                    '.h = -1',
                    'i = -0.5',
                    'j = 0',
                    'k = -0',
                    'l = 0.5',
                    'm = 1',
                    '# MyComment 01',
                    '; MyComment 01',
                    '[n-\\x5b-\\x5d-n]',
                    'o =',
                    'p = null',
                    'q = true',
                    'r = false',
                    's = ""',
                    '',
                ]),
            ],
        ];
    }

    /**
     * @dataProvider casesParse
     */
    public function testParse(array $expected, string $ini): void
    {
        static::assertSame($expected, (new IniSerializer())->parse($ini));
    }

    public function casesEmit(): array
    {
        return [
            'basic' => [
                implode(PHP_EOL, [
                    'a=b',
                    '',
                    '[c]',
                    '',
                    '[.d]',
                    '.d:e=null',
                    'f=true',
                    'g=false',
                    'h=-1',
                    'i=-0.5',
                    'j=0',
                    'k=0.5',
                    'l=1',
                    'm=n',
                    '',
                    '[o-\\x5b-\\x5d-p]',
                    'r=s',
                    '',
                ]),
                [
                    'a' => 'b',
                    'c' => [],
                    '.d' => [
                        '.d:e' => null,
                        'f' => true,
                        'g' => false,
                        'h' => -1,
                        'i' => -0.5,
                        'j' => 0,
                        'k' => 0.5,
                        'l' => 1,
                        'm' => 'n',
                    ],
                    'o-[-]-p' => [
                        'r' => 's',
                    ],
                ],
            ],
            'quoteStrings; spaceAroundEqualSign' => [
                implode(PHP_EOL, [
                    'a = "b"',
                    '',
                ]),
                [
                    'a' => 'b',
                ],
                [
                    'quoteStrings' => true,
                    'spaceAroundEqualSign' => true,
                ],
            ],
            'protectedValues' => [
                implode(PHP_EOL, [
                    'a="true"',
                    '',
                ]),
                [
                    'a' => 'true',
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesEmit
     */
    public function testEmit(string $expected, array $data, array $options = []): void
    {
        $serializer = new IniSerializer();
        $serializer->setOptions($options);

        static::assertSame($expected, $serializer->emit($data));
    }
}
