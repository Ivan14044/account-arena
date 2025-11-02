<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Service;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::orderBy('id', 'desc')->get();

        return view('admin.subscriptions.index', compact('subscriptions'));
    }

    public function edit(Subscription $subscription)
    {
        $services = Service::all();

        return view('admin.subscriptions.edit', compact('subscription', 'services'));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'status' => ['required'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
        ]);

        $subscription->update($validated);

        $redirectUrl = $request->input('return_url')
            ? redirect($request->input('return_url'))
            : redirect()->route('admin.subscriptions.index');

        return $redirectUrl->with('success', 'Subscription successfully updated.');
    }

    public function destroy(Request $request, Subscription $subscription)
    {
        $subscription->delete();

        $redirectUrl = $request->input('return_url')
            ? redirect($request->input('return_url'))
            : redirect()->route('admin.subscriptions.index');

        return $redirectUrl->with('success', 'Subscription successfully deleted.');
    }

    public function transactions(Subscription $subscription)
    {
        $transactions = $subscription->transactions()
            ->orderByDesc('created_at')
            ->get();
        $user = $subscription->user;

        return view('admin.subscriptions.transactions', compact('transactions', 'subscription', 'user'));
    }

    public function updateNextPayment(Request $request, Subscription $subscription)
    {
        $request->validate([
            'next_payment_at' => 'required|date',
        ]);

        $subscription->next_payment_at = $request->input('next_payment_at');
        $subscription->save();

        return redirect()->back()->with('success', 'Next payment date updated.');
    }

    public function toggleStatus(Request $request, Subscription $subscription)
    {
        $subscription->status = $subscription->status == Subscription::STATUS_ACTIVE ? Subscription::STATUS_CANCELED : Subscription::STATUS_ACTIVE;
        $subscription->save();

        return redirect()->back()->with('success', 'Subscription status updated successfully.');
    }
}
