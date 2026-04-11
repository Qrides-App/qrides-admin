<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateGeneralSettingRequest;
use App\Models\GeneralSetting;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class GeneralSettingController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('general_setting_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = GeneralSetting::query()->select(sprintf('%s.*', (new GeneralSetting)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'general_setting_show';
                $editGate = 'general_setting_edit';
                $deleteGate = 'general_setting_delete';
                $crudRoutePart = 'general-settings';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->editColumn('meta_key', function ($row) {
                return $row->meta_key ? $row->meta_key : '';
            });
            $table->editColumn('meta_value', function ($row) {
                return $row->meta_value ? $row->meta_value : '';
            });

            $table->rawColumns(['actions', 'placeholder']);

            return $table->make(true);
        }

        return view('admin.generalSettings.index');
    }

    public function edit(GeneralSetting $generalSetting)
    {
        abort_if(Gate::denies('general_setting_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.generalSettings.edit', compact('generalSetting'));
    }

    public function update(UpdateGeneralSettingRequest $request, GeneralSetting $generalSetting)
    {
        $generalSetting->update($request->all());

        return redirect()->route('admin.general-settings.index');
    }
}
