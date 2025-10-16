<?php

declare(strict_types = 1);

namespace Sweetchuck\IniSerializer\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sweetchuck\IniSerializer\IniSerializer;

#[CoversClass(IniSerializer::class)]
class IniSerializerTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    public static function casesParse(): array
    {
        return [
            'empty' => [
                'expected' => [],
                'ini' => '',
            ],
            'all-in-one' => [
                'expected' => [
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
                'ini' => implode(PHP_EOL, [
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
     * @param array<string, mixed> $expected
     */
    #[DataProvider('casesParse')]
    public function testParse(array $expected, string $ini): void
    {
        static::assertSame($expected, (new IniSerializer())->parse($ini));
    }

    /**
     * @return array<string, mixed>
     */
    public static function casesEmit(): array
    {
        return [
            'basic' => [
                'expected' => implode(PHP_EOL, [
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
                'data' => [
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
                'expected' => implode(PHP_EOL, [
                    'a = "b"',
                    '',
                ]),
                'data' => [
                    'a' => 'b',
                ],
                'options' => [
                    'quoteStrings' => true,
                    'spaceAroundEqualSign' => true,
                ],
            ],
            'protectedValues' => [
                'expected' => implode(PHP_EOL, [
                    'a="true"',
                    '',
                ]),
                'data' => [
                    'a' => 'true',
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $options
     */
    #[DataProvider('casesEmit')]
    public function testEmit(string $expected, array $data, array $options = []): void
    {
        $serializer = new IniSerializer();
        $serializer->setOptions($options);

        static::assertSame($expected, $serializer->emit($data));
    }
}
