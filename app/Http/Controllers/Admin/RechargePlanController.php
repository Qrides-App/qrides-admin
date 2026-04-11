<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriverRechargePlan;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;

class RechargePlanController extends Controller
{
    public function index()
    {
        $plans = DriverRechargePlan::orderBy('sort_order')
            ->orderBy('duration_days')
            ->get();

        return view('admin.rechargePlans.index', [
            'plans' => $plans,
            'amountPerDay' => GeneralSetting::getMetaValue('driver_recharge_amount_per_day') ?: 30,
            'currencyCode' => strtoupper(GeneralSetting::getMetaValue('driver_recharge_currency') ?: 'INR'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'duration_days' => 'required|integer|min:1|max:365',
            'amount' => 'required|numeric|min:1',
            'currency_code' => 'required|string|max:10',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $data['currency_code'] = strtoupper($data['currency_code']);
        $data['is_active'] = (int) ($request->boolean('is_active'));
        $data['sort_order'] = $data['sort_order'] ?? 0;

        DriverRechargePlan::create($data);

        return redirect()->route('admin.recharge-plans.index')->with('success', 'Recharge plan added successfully.');
    }

    public function update(Request $request, DriverRechargePlan $plan)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'duration_days' => 'required|integer|min:1|max:365',
            'amount' => 'required|numeric|min:1',
            'currency_code' => 'required|string|max:10',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $data['currency_code'] = strtoupper($data['currency_code']);
        $data['is_active'] = (int) ($request->boolean('is_active'));
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $plan->update($data);

        return redirect()->route('admin.recharge-plans.index')->with('success', 'Recharge plan updated successfully.');
    }

    public function destroy(DriverRechargePlan $plan)
    {
        $plan->delete();

        return redirect()->route('admin.recharge-plans.index')->with('success', 'Recharge plan deleted successfully.');
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'driver_recharge_amount_per_day' => 'required|numeric|min:1',
            'driver_recharge_currency' => 'required|string|max:10',
        ]);

        GeneralSetting::updateOrCreate(
            ['meta_key' => 'driver_recharge_amount_per_day'],
            ['meta_value' => $data['driver_recharge_amount_per_day']]
        );
        GeneralSetting::updateOrCreate(
            ['meta_key' => 'driver_recharge_currency'],
            ['meta_value' => strtoupper($data['driver_recharge_currency'])]
        );

        return redirect()->route('admin.recharge-plans.index')->with('success', 'Recharge settings updated successfully.');
    }
}

