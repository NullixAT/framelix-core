<?php

namespace Framelix\Framelix\Tests\Misc;

use Framelix\Framelix\Utils\PhpDocParser;
use PHPUnit\Framework\TestCase;

use function json_encode;
use function var_dump;

final class TestPhpDocParser extends TestCase
{

    public function tests(): void
    {
        $doc = "/**
     * Parse
     *   Multiline
     * @param string \$phpDocComment
     *  foo
     * @param \$notype
     *  foo
     * @return array ['description' => 'string', '@xxx' => ['xxx', 'xxx', ...]]
     */";
        $this->assertSame('{"description":["Parse","  Multiline"],"annotations":[{"type":"param","value":["string $phpDocComment"," foo"]},{"type":"param","value":["$notype"," foo"]},{"type":"return","value":["array [\'description\' => \'string\', \'@xxx\' => [\'xxx\', \'xxx\', ...]]"]}]}', json_encode(PhpDocParser::parse($doc)));
        $this->assertSame('{"phpDocComment":{"name":"phpDocComment","type":"string","description":[" foo"]},"notype":{"name":"notype","type":null,"description":[" foo"]}}', json_encode(PhpDocParser::parseVariableDescriptions($doc)));

    }
}
