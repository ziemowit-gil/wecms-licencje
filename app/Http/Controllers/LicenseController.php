<?php

namespace App\Http\Controllers;

use App\Licensing\LicenseIssuer;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LicenseController extends Controller
{
    public function __construct(private readonly LicenseIssuer $issuer) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status');

        return view('licenses.index', [
            'search' => $search,
            'status' => $status,
            'licenses' => License::with('customer')
                ->when(array_key_exists($status, License::STATUSES), fn ($q) => $q->inStatus($status))
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->where('domain', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"))))
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function create(Request $request)
    {
        return view('licenses.form', [
            'license' => new License(['edition' => 'standard', 'expires_on' => today()->addYear()]),
            'customers' => Customer::orderBy('name')->pluck('name', 'id'),
            'selectedCustomer' => $request->integer('customer') ?: null,
            'renewing' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, withCustomer: true);
        $license = $this->issuer->issue(Customer::findOrFail($data['customer_id']), $data);

        return redirect()->route('licenses.show', $license)->with('status', 'Klucz wystawiony. Skopiuj go i przekaż klientowi.');
    }

    public function show(License $license)
    {
        return view('licenses.show', [
            'license' => $license->load('customer', 'replacedBy', 'issuer'),
            'replaces' => License::where('replaced_by_id', $license->id)->first(),
            'activities' => Activity::with('user')->where('license_id', $license->id)->latest('id')->get(),
        ]);
    }

    /** Formularz nowego klucza w miejsce istniejącego (przedłużenie / zmiana edycji). */
    public function renewForm(License $license)
    {
        abort_unless($license->isCurrent(), 404);

        $base = $license->expires_on && $license->expires_on->isFuture() ? $license->expires_on : today();

        return view('licenses.form', [
            'license' => new License([
                'domain' => $license->domain,
                'edition' => $license->edition,
                'expires_on' => $base->copy()->addYear(),
                'note' => $license->note,
            ]),
            'customers' => collect(),
            'selectedCustomer' => $license->customer_id,
            'renewing' => $license,
        ]);
    }

    public function renew(Request $request, License $license)
    {
        abort_unless($license->isCurrent(), 404);

        $new = $this->issuer->renew($license, $this->validated($request, withCustomer: false));

        return redirect()->route('licenses.show', $new)->with('status', 'Wystawiono nowy klucz. Poprzedni oznaczono jako zastąpiony.');
    }

    public function revoke(Request $request, License $license)
    {
        abort_unless($license->isCurrent(), 404);

        $this->issuer->revoke($license, $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ])['reason']);

        return redirect()->route('licenses.show', $license)->with('status', 'Licencja unieważniona.');
    }

    public function download(License $license)
    {
        $content = implode("\n", [
            'Klucz licencyjny weCMS',
            'Klient: '.$license->customer->name,
            'Domena: '.$license->domain,
            'Edycja: '.$license->editionLabel(),
            'Ważny do: '.($license->expires_on?->format('d.m.Y') ?? 'bezterminowo'),
            'Wystawiony: '.$license->issued_on->format('d.m.Y'),
            '',
            'Wklej do pliku .env instalacji:',
            'LICENSE_KEY='.$license->license_key,
            '',
        ]);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="licencja-'.str_replace('*', 'dowolna', $license->domain).'-'.$license->id.'.txt"',
        ]);
    }

    private function validated(Request $request, bool $withCustomer): array
    {
        return $request->validate([
            'customer_id' => $withCustomer ? ['required', 'integer', 'exists:customers,id'] : ['exclude'],
            'domain' => ['required', 'string', 'max:255', 'regex:/^(\*|(https?:\/\/)?[a-z0-9.-]+\.[a-z]{2,}\/?)$/i'],
            'edition' => ['required', Rule::in(array_keys(config('licensing.editions')))],
            'expires_on' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:120'],
        ], [
            'domain.regex' => 'Podaj domenę, np. klient.pl, albo * dla dowolnej domeny.',
            'expires_on.after_or_equal' => 'Data ważności nie może być w przeszłości.',
        ]);
    }
}
