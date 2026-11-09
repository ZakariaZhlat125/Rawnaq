<?php

namespace Tests\Unit;

use App\Services\QrTextFormatter;
use PHPUnit\Framework\TestCase;

class QrTextFormatterTest extends TestCase
{
    public function test_it_preserves_plain_text_line_breaks_and_escapes_characters(): void
    {
        $html = (new QrTextFormatter())->format("رونق & العناية\nالسطر الثاني");
        $this->assertStringContainsString('رونق &amp; العناية', $html);
        $this->assertStringContainsString('<br', $html);
    }

    public function test_it_keeps_editor_formatting(): void
    {
        $html = (new QrTextFormatter())->format('<h2>عنوان</h2><p style="text-align: center; color: #ff0000;"><strong>نص</strong></p><ul><li>عنصر</li></ul>');
        $this->assertStringContainsString('<h2>عنوان</h2>', $html);
        $this->assertStringContainsString('<strong>نص</strong>', $html);
        $this->assertStringContainsString('text-align:center', str_replace(' ', '', $html));
        $this->assertStringContainsString('<li>عنصر</li>', $html);
    }

    public function test_it_removes_scripts_events_and_unsafe_links(): void
    {
        $html = (new QrTextFormatter())->format('<script>alert(1)</script><p onclick="alert(1)">نص</p><a href="javascript:alert(1)">رابط</a><img src="x" onerror="alert(1)">');
        foreach (['<script', 'onclick', 'javascript:', '<img', 'onerror'] as $unsafe) {
            $this->assertStringNotContainsString($unsafe, $html);
        }
        $this->assertStringContainsString('نص', $html);
    }

    public function test_it_can_render_saved_text_without_double_encoding(): void
    {
        $formatter = new QrTextFormatter();
        $saved = $formatter->format('رونق & العناية');
        $this->assertSame($saved, $formatter->format($saved));
    }
}
