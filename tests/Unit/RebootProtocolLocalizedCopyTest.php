<?php

namespace Tests\Unit;

use App\Support\RebootProtocolLocalizedCopy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RebootProtocolLocalizedCopyTest extends TestCase
{
    #[Test]
    public function test_it_localizes_reboot_report_fields_for_farsi(): void
    {
        $report = [
            'phase' => ['en' => 'Emotional Withdrawal', 'fa' => 'مرحله ترک عاطفی'],
            'phase_narrative' => [
                'en' => 'Your brain is craving familiar attachment.',
                'fa' => 'مغزت دلبستگی آشنا را می‌خواهد.',
            ],
            'first_prescription' => [
                'en' => 'English prescription',
                'fa' => 'نسخه فارسی',
            ],
            'stability_score' => 54,
            'content' => [
                'sections' => [
                    [
                        'heading_en' => 'Main risk',
                        'heading_fa' => 'ریسک اصلی',
                        'body_en' => 'English risk',
                        'body_fa' => 'ریسک فارسی',
                    ],
                ],
            ],
        ];

        $localizedReport = RebootProtocolLocalizedCopy::localizeReport($report, 'fa');
        $content = RebootProtocolLocalizedCopy::localizeContent($report, 'fa');

        $this->assertSame('مرحله ترک عاطفی', $localizedReport['title']);
        $this->assertStringContainsString('امتیاز ثبات بازیابی', $content['tagline']);
        $this->assertStringContainsString('منطقه خاکستری', $content['tagline']);
        $this->assertSame('مغزت دلبستگی آشنا را می‌خواهد.', $content['narrative']);
        $this->assertSame('نسخه فارسی', $content['prescription']);
        $this->assertSame('ریسک اصلی', $content['sections'][0]['heading']);
        $this->assertSame('ریسک فارسی', $content['sections'][0]['body']);
        $this->assertStringNotContainsString('Emotional Withdrawal', $content['archetype']);
        $this->assertStringNotContainsString('Your brain', $content['narrative']);
    }
}
