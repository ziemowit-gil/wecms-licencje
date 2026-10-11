<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        return view('customers.index', [
            'search' => $search,
            'customers' => Customer::withCount('licenses')
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")))
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('customers.form', ['customer' => new Customer]);
    }

    public function store(Request $request)
    {
        $customer = Customer::create($this->validated($request));
        Activity::log("Dodano klienta {$customer->name}.", customer: $customer);

        return redirect()->route('customers.show', $customer)->with('status', 'Klient dodany.');
    }

    public function show(Customer $customer)
    {
        return view('customers.show', ['customer' => $customer->load('licenses')]);
    }

    public function edit(Customer $customer)
    {
        return view('customers.form', ['customer' => $customer]);
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validated($request));
        Activity::log("Zmieniono dane klienta {$customer->name}.", customer: $customer);

        return redirect()->route('customers.show', $customer)->with('status', 'Zapisano zmiany.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->licenses()->exists()) {
            return back()->with('error', 'Nie można usunąć klienta, który ma licencje.');
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Klient usunięty.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
