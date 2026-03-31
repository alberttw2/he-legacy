<?php

use PHPUnit\Framework\TestCase;

class PurifierTest extends TestCase
{
    public function testStripsScriptTags()
    {
        $p = new Purifier();
        $p->set_config('text');
        $result = $p->purify('<b>hello</b><script>alert(1)</script>');
        $this->assertStringContainsString('<b>hello</b>', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testAllowsLinksInMail()
    {
        $p = new Purifier();
        $p->set_config('mail');
        $result = $p->purify('<a href="http://example.com">link</a>');
        $this->assertStringContainsString('<a', $result);
    }

    public function testStripsEverythingInLog()
    {
        $p = new Purifier();
        $p->set_config('log');
        $result = $p->purify('<b>test</b> <a href="x">link</a>');
        $this->assertEquals('test link', $result);
    }

    public function testStripsOnClickAttributes()
    {
        $p = new Purifier();
        $p->set_config('text');
        $result = $p->purify('<b onclick="alert(1)">test</b>');
        $this->assertStringNotContainsString('onclick', $result);
    }
}
