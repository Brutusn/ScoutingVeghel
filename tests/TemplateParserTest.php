<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../php/templateParser.php';

final class TemplateParserTest extends TestCase
{
    private string $templatePath;
    private string $partialPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templatePath = tempnam(sys_get_temp_dir(), 'tpl');
        $this->partialPath = tempnam(sys_get_temp_dir(), 'partial');

        file_put_contents($this->templatePath, 'Hello {name}! {content}');
        file_put_contents($this->partialPath, 'Included snippet');
    }

    protected function tearDown(): void
    {
        @unlink($this->templatePath);
        @unlink($this->partialPath);
        parent::tearDown();
    }

    public function testTemplateParserReplacesTagsAndIncludesFiles(): void
    {
        $parser = new templateParser($this->templatePath);
        $parser->parseTemplate([
            'name' => 'Scouting Veghel',
            'content' => $this->partialPath,
        ]);

        $output = $parser->display();
        $this->assertStringContainsString('Hello Scouting Veghel!', $output);
        $this->assertStringContainsString('Included snippet', $output);
    }
}
