<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('balance_filter')) {
            $filter = $request->input('balance_filter');
            if ($filter === 'debt') {
                $query->where('balance', '>', 0);
            } elseif ($filter === 'clean') {
                $query->where('balance', '<=', 0);
            }
        }

        $customers = $query->orderBy('name')->get();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'balance_filter']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'max_credit_limit' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['balance'] = 0.00;

        Customer::create($validated);

        return redirect()->back()->with('success', 'Cliente cadastrado com sucesso.');
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'sales' => function ($query) {
                $query->orderBy('sale_date', 'desc');
            },
            'transactions' => function ($query) {
                $query->orderBy('transaction_date', 'desc');
            }
        ]);

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'max_credit_limit' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $customer->update($validated);

        return redirect()->back()->with('success', 'Cliente atualizado com sucesso.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->sales()->count() > 0) {
            return redirect()->back()->withErrors(['error' => 'Não é possível excluir um cliente que possui vendas registradas.']);
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Cliente excluído com sucesso.');
    }

    /**
     * Store a manual credit or debit transaction (e.g. paying down balance, cash payments).
     */
    public function storeTransaction(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'type' => 'required|in:debit,credit',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        if ($validated['type'] === 'debit') {
            $customer->addDebit($validated['amount'], $validated['description']);
        } else {
            $customer->addCredit($validated['amount'], $validated['description']);
        }

        return redirect()->back()->with('success', 'Transação registrada e saldo atualizado com sucesso.');
    }
}
