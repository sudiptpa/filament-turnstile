<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Tests\Feature;

use Filament\Auth\MultiFactor\Email\Notifications\VerifyEmailAuthentication;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Sujip\Filament\Turnstile\Contracts\TurnstileClientContract;
use Sujip\Filament\Turnstile\Pages\Auth\Login;
use Sujip\Filament\Turnstile\Tests\Support\FakeTurnstileClient;
use Sujip\Filament\Turnstile\Tests\Support\TestPanelProvider;
use Sujip\Filament\Turnstile\Tests\Support\TestUser;
use Sujip\Filament\Turnstile\Tests\TestCase;

final class LoginMultiFactorTest extends TestCase
{
    private FakeTurnstileClient $turnstile;

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            \Livewire\LivewireServiceProvider::class,
            \BladeUI\Icons\BladeIconsServiceProvider::class,
            \BladeUI\Heroicons\BladeHeroiconsServiceProvider::class,
            \Filament\Support\SupportServiceProvider::class,
            \Filament\Actions\ActionsServiceProvider::class,
            \Filament\Forms\FormsServiceProvider::class,
            \Filament\Infolists\InfolistsServiceProvider::class,
            \Filament\Notifications\NotificationsServiceProvider::class,
            \Filament\Schemas\SchemasServiceProvider::class,
            \Filament\FilamentServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $app['config']->set('auth.providers.users.model', TestUser::class);
        $app['config']->set('session.driver', 'array');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(\Livewire\Mechanisms\DataStore::class);

        Schema::create('users', function ($table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('mfa')->default(false);
            $table->boolean('blocked')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        $this->turnstile = new FakeTurnstileClient();
        $this->app->instance(TurnstileClientContract::class, $this->turnstile);

        Filament::setCurrentPanel('admin');
        Notification::fake();
    }

    private function user(string $email, bool $mfa): TestUser
    {
        return TestUser::create(['email' => $email, 'password' => 'secret-pass', 'mfa' => $mfa]);
    }

    private function submit($component, string $email, string $token, string $password = 'secret-pass')
    {
        return $component
            ->set('data.email', $email)
            ->set('data.password', $password)
            ->set('turnstileToken', $token)
            ->call('authenticate');
    }

    private function codesSent(): int
    {
        return TestUser::query()->get()->sum(fn (TestUser $u): int => Notification::sent($u, VerifyEmailAuthentication::class)->count());
    }

    public function test_non_mfa_login_verifies_once(): void
    {
        $this->user('a@example.com', false);

        $this->submit(Livewire::test(Login::class), 'a@example.com', 't1')->assertHasNoErrors();

        $this->assertSame(1, $this->turnstile->verifyCalls);
        $this->assertTrue(Filament::auth()->check());
    }

    public function test_missing_or_rejected_token_blocks_login_and_code(): void
    {
        $this->user('m@example.com', true);

        $this->submit(Livewire::test(Login::class), 'm@example.com', '')->assertHasErrors(['turnstileToken']);
        $this->submit(Livewire::test(Login::class), 'm@example.com', 'bad')->assertHasErrors(['turnstileToken']);

        $this->assertFalse(Filament::auth()->check());
        $this->assertSame(0, $this->codesSent());
    }

    public function test_wrong_credentials_allow_fresh_token_retry(): void
    {
        $this->user('a@example.com', false);

        $c = Livewire::test(Login::class);
        $this->submit($c, 'a@example.com', 't1', 'wrong')->assertHasErrors(['data.email'])->assertDispatched('turnstile.reset');
        $this->assertSame('', $c->get('turnstileToken'));

        $this->submit($c, 'a@example.com', 't2')->assertHasNoErrors();
        $this->assertSame(2, $this->turnstile->verifyCalls);
        $this->assertTrue(Filament::auth()->check());
    }

    public function test_rate_limited_credentials_attempt_resets_widget(): void
    {
        $this->user('a@example.com', false);
        $c = Livewire::test(Login::class);

        for ($i = 1; $i <= 5; $i++) {
            $this->submit($c, 'a@example.com', "w{$i}", 'wrong');
        }

        $this->submit($c, 'a@example.com', 'limited')->assertDispatched('turnstile.reset');
        $this->assertSame('', $c->get('turnstileToken'));
        $this->assertFalse(Filament::auth()->check());
    }

    public function test_mfa_flow_verifies_once_and_code_needs_no_captcha(): void
    {
        $user = $this->user('m@example.com', true);

        $c = $this->submit(Livewire::test(Login::class), 'm@example.com', 't1');

        $this->assertSame(1, $this->turnstile->verifyCalls);
        $this->assertFalse(Filament::auth()->check());
        $this->assertNotEmpty($c->get('userUndertakingMultiFactorAuthentication'));
        Notification::assertSentTo($user, VerifyEmailAuthentication::class);
        $this->assertSame('', $c->get('turnstileToken'));

        $c->set('data.multiFactor.email_code.code', '123456')->call('authenticate')->assertHasNoErrors();

        $this->assertSame(1, $this->turnstile->verifyCalls);
        $this->assertTrue(Filament::auth()->check());
    }

    public function test_wrong_code_stays_guest_without_captcha(): void
    {
        $this->user('m@example.com', true);

        $c = $this->submit(Livewire::test(Login::class), 'm@example.com', 't1');
        $c->set('data.multiFactor.email_code.code', '000000')->call('authenticate')->assertHasErrors();

        $this->assertSame(1, $this->turnstile->verifyCalls);
        $this->assertFalse(Filament::auth()->check());
        $this->assertNotEmpty($c->get('userUndertakingMultiFactorAuthentication'));
    }

    public function test_switching_account_during_challenge_requires_fresh_captcha(): void
    {
        $this->user('m@example.com', true);
        $other = $this->user('n@example.com', false);
        $victim = $this->user('v@example.com', true);

        $c = $this->submit(Livewire::test(Login::class), 'm@example.com', 't1');
        $this->assertSame(1, $this->turnstile->verifyCalls);

        // switch to a non-MFA account
        $c->set('data.email', $other->email)->call('authenticate')->assertHasErrors(['turnstileToken']);
        $this->assertFalse(Filament::auth()->check());
        $this->assertEmpty($c->get('userUndertakingMultiFactorAuthentication'));

        // switch to another MFA account must not send a code without captcha
        $c2 = $this->submit(Livewire::test(Login::class), 'm@example.com', 't2');
        $c2->set('data.email', $victim->email)->call('authenticate')->assertHasErrors(['turnstileToken']);
        Notification::assertNotSentTo($victim, VerifyEmailAuthentication::class);
        $this->assertSame(2, $this->turnstile->verifyCalls);
    }

    public function test_unconfigured_turnstile_works_with_and_without_mfa(): void
    {
        $this->turnstile->configured = false;
        $user = $this->user('m@example.com', true);
        $this->user('a@example.com', false);

        $c = $this->submit(Livewire::test(Login::class), 'm@example.com', '');
        Notification::assertSentTo($user, VerifyEmailAuthentication::class);
        $c->set('data.multiFactor.email_code.code', '123456')->call('authenticate');
        $this->assertTrue(Filament::auth()->check());

        Filament::auth()->logout();
        $this->submit(Livewire::test(Login::class), 'a@example.com', '');
        $this->assertTrue(Filament::auth()->check());
        $this->assertSame(0, $this->turnstile->verifyCalls);
    }
}
