@extends('layouts.admin')
@section('content')
<section class="content">
    <div class="row gap-2">
        <div class="col-md-3 settings_bar_gap">
            <div class="box box-info box_info">
                <div class="">
                    <h4 class="all_settings f-18 mt-1" style="margin-left:15px;">QR Hire Settings</h4>
                    @include('admin.generalSettings.general-setting-links.links')
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">QR Hire Pricing</h3>
                </div>
                <form method="post" action="{{ route('admin.hireSettingUpdate') }}" class="form-horizontal">
                    {{ csrf_field() }}
                    <div class="box-body">
                        <div class="alert alert-info" style="margin: 10px 15px;">
                            <strong>How this works:</strong>
                            <ul style="padding-left:18px;margin:6px 0;">
                                <li>Rate per hour is the base price; duration options can override it with <code>amount</code> or apply a <code>multiplier</code>.</li>
                                <li>Custom hours typed by riders are clamped between Min and Max.</li>
                                <li>Keep 3–6 duration options so riders see clear choices.</li>
                            </ul>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Rate Per Hour</label>
                            <div class="col-sm-6">
                                <input type="number" step="0.01" min="0" name="hire_rate_per_hour" class="form-control"
                                    value="{{ old('hire_rate_per_hour', $hire_rate_per_hour) }}" required>
                                <small class="text-muted">Base hourly rate used when no fixed amount is provided.</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Hire Currency Code</label>
                            <div class="col-sm-6">
                                <input type="text" name="hire_currency" class="form-control" maxlength="10"
                                    value="{{ old('hire_currency', $hire_currency) }}" required>
                                <small class="text-muted">3–10 chars. Example: INR, USD. We store it upper-case.</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Custom Hours Min / Max</label>
                            <div class="col-sm-3">
                                <input type="number" min="1" name="hire_custom_min_hours" class="form-control"
                                    value="{{ old('hire_custom_min_hours', $hire_custom_min_hours) }}" required>
                            </div>
                            <div class="col-sm-3">
                                <input type="number" min="1" name="hire_custom_max_hours" class="form-control"
                                    value="{{ old('hire_custom_max_hours', $hire_custom_max_hours) }}" required>
                            </div>
                            <div class="col-sm-3">
                                <small class="text-muted">Bounds for user-entered hour values; we auto-clamp min ≤ max.</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Duration Options JSON</label>
                            <div class="col-sm-6">
                                <textarea name="hire_duration_options_json" id="hire_duration_options_json" rows="5" class="form-control" style="font-family: Menlo, Monaco, Consolas, 'Courier New', monospace;"
                                    placeholder='[{"key":"4h","label":"4 hours","hours":4},{"key":"6h","label":"6 hours","hours":6,"multiplier":0.95},{"key":"1d","label":"1 day","hours":24,"multiplier":0.9}]'>{{ old('hire_duration_options_json', $hire_duration_options_json) }}</textarea>
                                <div style="margin-top:6px; display:flex; gap:8px; flex-wrap: wrap;">
                                    <button type="button" class="btn btn-default btn-xs" onclick="fillHirePreset('defaults')">Use default options</button>
                                    <button type="button" class="btn btn-default btn-xs" onclick="fillHirePreset('city')">City preset</button>
                                    <button type="button" class="btn btn-default btn-xs" onclick="fillHirePreset('blank')">Clear</button>
                                </div>
                                <div style="margin-top:6px;">
                                    <span id="hire_json_status" class="label label-default">Waiting…</span>
                                </div>
                                <small class="text-muted">Each option: key, label, hours, optional amount (fixed price) or multiplier (discount/surcharge).</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Preview (computed with current rate)</label>
                            <div class="col-sm-6">
                                <div id="hire_preview" class="well well-sm" style="margin-bottom:0; padding:10px; font-family: Menlo, Monaco, Consolas, 'Courier New', monospace; min-height:54px;">
                                    <span class="text-muted">No options yet.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-info btn-space">Save</button>
                        <a class="btn btn-danger" href="{{ route('admin.settings') }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
@section('scripts')
<script>
    const presetDefaults = [
        {"key":"4h","label":"4 hours","hours":4},
        {"key":"6h","label":"6 hours","hours":6,"multiplier":0.95},
        {"key":"1d","label":"1 day","hours":24,"multiplier":0.9}
    ];
    const presetCity = [
        {"key":"2h","label":"2 hours (city hop)","hours":2,"multiplier":1},
        {"key":"4h","label":"Half-day","hours":4,"multiplier":0.95},
        {"key":"8h","label":"Full-day","hours":8,"multiplier":0.9},
        {"key":"1d","label":"24 hours","hours":24,"multiplier":0.85}
    ];
    function fillHirePreset(type) {
        const field = document.getElementById('hire_duration_options_json');
        if (!field) return;
        if (type === 'defaults') {
            field.value = JSON.stringify(presetDefaults, null, 2);
        } else if (type === 'city') {
            field.value = JSON.stringify(presetCity, null, 2);
        } else {
            field.value = '';
        }
        updateHirePreview();
    }

    function updateHirePreview() {
        const status = document.getElementById('hire_json_status');
        const preview = document.getElementById('hire_preview');
        const rateField = document.querySelector('input[name=\"hire_rate_per_hour\"]');
        const textarea = document.getElementById('hire_duration_options_json');
        if (!status || !preview || !rateField || !textarea) return;

        let rate = parseFloat(rateField.value);
        if (Number.isNaN(rate) || rate < 0) rate = 0;

        const raw = textarea.value.trim();
        if (raw === '') {
            status.className = 'label label-default';
            status.textContent = 'Using defaults at runtime';
            preview.innerHTML = '<span class=\"text-muted\">Defaults will be applied (4h / 6h / 1d).</span>';
            return;
        }

        try {
            const parsed = JSON.parse(raw);
            if (!Array.isArray(parsed) || parsed.length === 0) {
                throw new Error('Must be a non-empty array');
            }
            const rows = parsed.map(opt => {
                const hours = Number(opt.hours) || 0;
                if (hours <= 0) throw new Error('Hours must be > 0');
                const amount = opt.amount && opt.amount > 0
                    ? Number(opt.amount)
                    : parseFloat((hours * rate * (opt.multiplier ? Number(opt.multiplier) : 1)).toFixed(2));
                const key = opt.key || `${hours}h`;
                const label = opt.label || key;
                return `<div><strong>${label}</strong> (${hours}h) → ${amount}</div>`;
            });
            status.className = 'label label-success';
            status.textContent = 'Valid JSON';
            preview.innerHTML = rows.join('');
        } catch (e) {
            status.className = 'label label-danger';
            status.textContent = 'Invalid JSON';
            preview.innerHTML = `<span class=\"text-danger\">${e.message}</span>`;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateHirePreview();
        const textarea = document.getElementById('hire_duration_options_json');
        const rateField = document.querySelector('input[name=\"hire_rate_per_hour\"]');
        if (textarea) textarea.addEventListener('input', updateHirePreview);
        if (rateField) rateField.addEventListener('input', updateHirePreview);
    });
</script>
@endsection
