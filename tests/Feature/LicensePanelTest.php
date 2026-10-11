<?php

namespace Tests\Feature;

use App\Licensing\LicenseKey;
use App\Licensing\LicenseSigner;
use App\Models\Customer;
use App\Models\License;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicensePanelTest extends TestCase
{
    use RefreshDatabase;

    private string $keyPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keyPath = tempnam(sys_get_temp_dir(), 'lic');
        openssl_pkey_export(openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]), $pem);
        file_put_contents($this->keyPath, $pem);
        config(['licensing.private_key_path' => $this->keyPath]);
    }

    protected function tearDown(): void
    {
        @unlink($this->keyPath);

        parent::tearDown();
    }

    private function issue(Customer $customer, array $overrides = []): License
    {
        $this->post(route('licenses.store'), $overrides + [
            'customer_id' => $customer->id,
            'domain' => 'https://www.Klient.pl/',
            'edition' => 'premium',
            'expires_on' => today()->addYear()->toDateString(),
            'note' => 'Umowa 1/2026',
        ])->assertRedirect();

        return License::latest('id')->firstOrFail();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk()->assertSee('Zaloguj się');
    }

    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'tajne-haslo-123']);

        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'zle'])
            ->assertSessionHasErrors('email');
        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'tajne-haslo-123'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_issued_key_is_signed_and_normalized(): void
    {
        $this->actingAs(User::factory()->create());
        $customer = Customer::create(['name' => 'Fundacja Testowa']);

        $license = $this->issue($customer);

        $this->assertSame('klient.pl', $license->domain);
        $this->assertSame(LicenseKey::hash($license->license_key), $license->key_hash);

        $payload = app(LicenseSigner::class)->verify(strtolower($license->license_key));
        $this->assertSame(['d' => 'klient.pl', 'x' => 'premium', 'n' => 'Umowa 1/2026'],
            array_intersect_key($payload, array_flip(['d', 'x', 'n'])));

        $this->get(route('licenses.show', $license))->assertOk()
            ->assertSee($license->license_key)
            ->assertSee('Aktywna');
        $this->get(route('licenses.download', $license))->assertOk()
            ->assertSee('LICENSE_KEY='.$license->license_key);
    }

    /** Klucz z panelu MUSI przechodzić walidator weCMS (feer-web App\Support\LicenseValidator). */
    public function test_key_format_matches_wecms_validator(): void
    {
        $validator = dirname(base_path()).'/feer-web/app/Support/LicenseValidator.php';
        if (! is_file($validator)) {
            $this->markTestSkipped('Brak repozytorium feer-web obok panelu.');
        }

        $this->actingAs(User::factory()->create());
        $license = $this->issue(Customer::create(['name' => 'Klient']));

        $source = str_replace('namespace App\Support;', 'namespace WecmsCheck;', file_get_contents($validator));
        $source = str_replace("config('license.public_key')", '$GLOBALS[\'__wecms_public_key\']', $source);
        $GLOBALS['__wecms_public_key'] = app(LicenseSigner::class)->publicKeyPem();
        if (! class_exists(\WecmsCheck\LicenseValidator::class)) {
            eval('?>'.$source);
        }

        $this->assertNotNull(\WecmsCheck\LicenseValidator::validate($license->license_key, 'https://klient.pl'));
        $this->assertNull(\WecmsCheck\LicenseValidator::validate($license->license_key, 'https://inny.pl'));
    }

    public function test_renew_issues_new_key_and_marks_old_as_replaced(): void
    {
        $this->actingAs(User::factory()->create());
        $old = $this->issue(Customer::create(['name' => 'Klient']));

        $this->get(route('licenses.renew-form', $old))->assertOk();
        $this->post(route('licenses.renew', $old), [
            'domain' => 'klient.pl',
            'edition' => 'standard',
            'expires_on' => today()->addYears(2)->toDateString(),
        ])->assertRedirect();

        $new = License::latest('id')->first();
        $this->assertNotSame($old->id, $new->id);
        $this->assertSame($new->id, $old->fresh()->replaced_by_id);
        $this->assertSame('replaced', $old->fresh()->status());
        $this->assertSame('standard', $new->edition);

        $this->get(route('licenses.renew-form', $old))->assertNotFound();
    }

    public function test_revoke_and_online_check(): void
    {
        $this->actingAs(User::factory()->create());
        $license = $this->issue(Customer::create(['name' => 'Klient']));

        $this->postJson(route('api.licenses.check'), ['key' => $license->license_key, 'domain' => 'https://www.klient.pl'])
            ->assertOk()->assertJson(['valid' => true, 'edition' => 'premium']);
        $this->postJson(route('api.licenses.check'), ['key' => $license->license_key, 'domain' => 'inny.pl'])
            ->assertJson(['valid' => false, 'reason' => 'domain_mismatch']);
        $this->assertSame('inny.pl', $license->fresh()->last_check_domain, 'zapisuje domenę ostatniego zapytania');

        $this->post(route('licenses.revoke', $license), ['reason' => 'Brak płatności'])->assertRedirect();
        $this->assertSame('revoked', $license->fresh()->status());

        $this->postJson(route('api.licenses.check'), ['key' => $license->license_key, 'domain' => 'klient.pl'])
            ->assertJson(['valid' => false, 'reason' => 'revoked']);
        $this->postJson(route('api.licenses.check'), ['key' => 'WCMS-AAAA.BBBB', 'domain' => 'klient.pl'])
            ->assertJson(['valid' => false, 'reason' => 'not_found']);
    }

    public function test_status_filters_and_dashboard(): void
    {
        $this->actingAs(User::factory()->create());
        $customer = Customer::create(['name' => 'Klient']);
        $soon = $this->issue($customer, ['domain' => 'wkrotce.pl', 'expires_on' => today()->addDays(10)->toDateString()]);
        $this->issue($customer, ['domain' => 'dlugo.pl']);

        $this->assertSame('expiring', $soon->status());
        $this->get(route('licenses.index', ['status' => 'expiring']))->assertOk()
            ->assertSee('wkrotce.pl')->assertDontSee('dlugo.pl');
        $this->get(route('dashboard'))->assertOk()->assertSee('wkrotce.pl');
    }

    public function test_customer_with_licenses_cannot_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('customers.store'), ['name' => 'Nowy klient', 'email' => 'a@b.pl'])->assertRedirect();
        $customer = Customer::firstOrFail();

        $this->issue($customer);
        $this->delete(route('customers.destroy', $customer))->assertSessionHas('error');
        $this->assertModelExists($customer);
    }

    public function test_validation_messages_are_polish(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('licenses.store'), ['domain' => 'nie domena', 'edition' => 'x'])
            ->assertSessionHasErrors([
                'customer_id' => 'Pole klient jest wymagane.',
                'domain' => 'Podaj domenę, np. klient.pl, albo * dla dowolnej domeny.',
            ]);
    }
}
