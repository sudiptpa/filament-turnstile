<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Tests\Feature;

use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Sujip\Filament\Turnstile\Tests\TestCase;

final class WidgetViewTest extends TestCase
{
    public function test_login_widget_cleans_up_when_removed(): void
    {
        view()->share('errors', new ViewErrorBag());

        $html = view('filament-turnstile::turnstile-raw', ['siteKey' => 'site-key'])->render();

        $this->assertStringContainsString('destroy()', $html);
        $this->assertStringContainsString('turnstile.remove(', $html);
    }

    #[DataProvider('widgetViews')]
    public function test_widget_views_ignore_a_reset_for_a_removed_widget(string $view): void
    {
        $source = (string) file_get_contents(__DIR__.'/../../resources/views/'.$view);

        $this->assertStringContainsString('try {', $source);
        $this->assertStringContainsString('turnstile.reset(this.widgetId)', $source);
        $this->assertStringContainsString('stopListening', $source);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function widgetViews(): array
    {
        return [
            'login widget' => ['turnstile-raw.blade.php'],
            'form field' => ['turnstile.blade.php'],
        ];
    }
}
