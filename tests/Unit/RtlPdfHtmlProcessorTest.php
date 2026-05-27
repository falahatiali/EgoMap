<?php

namespace Tests\Unit;

use App\Services\Pdf\RtlPdfHtmlProcessor;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RtlPdfHtmlProcessorTest extends TestCase
{
    #[Test]
    public function test_it_reshapes_persian_text_inside_html(): void
    {
        $processor = new RtlPdfHtmlProcessor;

        $html = '<p>سلام دنیا</p>';
        $processed = $processor->process($html, 'fa');

        $this->assertNotSame($html, $processed);
        $this->assertStringContainsString('<p>', $processed);
    }

    #[Test]
    public function test_it_leaves_english_html_unchanged_for_ltr_locale(): void
    {
        $processor = new RtlPdfHtmlProcessor;

        $html = '<p>Hello world</p>';

        $this->assertSame($html, $processor->process($html, 'en'));
    }
}
