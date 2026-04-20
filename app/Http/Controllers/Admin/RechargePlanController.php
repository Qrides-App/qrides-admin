<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriverRechargePlan;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RechargePlanController extends Controller
{
    public function index()
    {
        $amountPerDay = (float) (GeneralSetting::getMetaValue('driver_recharge_amount_per_day') ?: 30);
        $currencyCode = strtoupper(GeneralSetting::getMetaValue('driver_recharge_currency') ?: 'INR');
        $gstPercentage = $this->driverRechargeGstPercentage();

        $plans = collect();
        if (Schema::hasTable('driver_recharge_plans')) {
            $this->syncDailyRechargePlan($amountPerDay, $currencyCode);
            $plans = DriverRechargePlan::orderBy('sort_order')
                ->orderBy('duration_days')
                ->get();
        } else {
            session()->flash('warning', 'Recharge plans table is missing. Run migrations or schema ensure command.');
        }

        return view('admin.rechargePlans.index', [
            'plans' => $plans,
            'amountPerDay' => $amountPerDay,
            'currencyCode' => $currencyCode,
            'gstPercentage' => $gstPercentage,
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

        if (! Schema::hasTable('driver_recharge_plans')) {
            return redirect()->route('admin.recharge-plans.index')->with('error', 'Recharge plans table is missing. Please run migrations.');
        }

        DriverRechargePlan::create($data);

        return redirect()->route('admin.recharge-plans.index')->with('success', 'Recharge plan added successfully.');
    }

    public function update(Request $request, $plan)
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

        if (! Schema::hasTable('driver_recharge_plans')) {
            return redirect()->route('admin.recharge-plans.index')->with('error', 'Recharge plans table is missing. Please run migrations.');
        }

        $plan = DriverRechargePlan::findOrFail($plan);
        $plan->update($data);

        return redirect()->route('admin.recharge-plans.index')->with('success', 'Recharge plan updated successfully.');
    }

    public function destroy($plan)
    {
        if (! Schema::hasTable('driver_recharge_plans')) {
            return redirect()->route('admin.recharge-plans.index')->with('error', 'Recharge plans table is missing. Please run migrations.');
        }

        $plan = DriverRechargePlan::findOrFail($plan);
        $plan->delete();

        return redirect()->route('admin.recharge-plans.index')->with('success', 'Recharge plan deleted successfully.');
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'driver_recharge_amount_per_day' => 'required|numeric|min:1',
            'driver_recharge_currency' => 'required|string|max:10',
            'driver_recharge_gst_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        GeneralSetting::updateOrCreate(
            ['meta_key' => 'driver_recharge_amount_per_day'],
            ['meta_value' => $data['driver_recharge_amount_per_day']]
        );
        GeneralSetting::updateOrCreate(
            ['meta_key' => 'driver_recharge_currency'],
            ['meta_value' => strtoupper($data['driver_recharge_currency'])]
        );
        GeneralSetting::updateOrCreate(
            ['meta_key' => 'driver_recharge_gst_percentage'],
            ['meta_value' => $data['driver_recharge_gst_percentage'] ?? 0]
        );

        $this->syncDailyRechargePlan(
            (float) $data['driver_recharge_amount_per_day'],
            strtoupper($data['driver_recharge_currency'])
        );

        return redirect()->route('admin.recharge-plans.index')->with('success', 'Recharge settings updated successfully.');
    }

    private function syncDailyRechargePlan(float $amountPerDay, string $currencyCode): void
    {
        if (! Schema::hasTable('driver_recharge_plans')) {
            return;
        }

        DriverRechargePlan::updateOrCreate(
            ['duration_days' => 1],
            [
                'name' => 'Daily Plan',
                'amount' => round($amountPerDay, 2),
                'currency_code' => strtoupper($currencyCode),
                'is_active' => 1,
                'sort_order' => 1,
            ]
        );
    }

    private function driverRechargeGstPercentage(): float
    {
        return round((float) (GeneralSetting::getMetaValue('driver_recharge_gst_percentage') ?: 0), 2);
    }
}
