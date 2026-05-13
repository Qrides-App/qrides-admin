<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\StoreAllPackageRequest;
use App\Http\Requests\UpdateAllPackageRequest;
use App\Http\Resources\Admin\AllPackageResource;
use App\Models\AllPackage;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllPackagesApiController extends Controller
{
    use MediaUploadingTrait {
        storeMedia as protected traitStoreMedia;
    }

    public function index()
    {
        abort_if(Gate::denies('all_package_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new AllPackageResource(AllPackage::all());
    }

    public function store(StoreAllPackageRequest $request)
    {
        abort_if(Gate::denies('all_package_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $allPackage = AllPackage::create($request->all());

        if ($request->input('package_image', false)) {
            $allPackage->addMedia(storage_path('tmp/uploads/'.basename($request->input('package_image'))))->toMediaCollection('package_image');
        }

        return (new AllPackageResource($allPackage))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(AllPackage $allPackage)
    {
        abort_if(Gate::denies('all_package_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new AllPackageResource($allPackage);
    }

    public function update(UpdateAllPackageRequest $request, AllPackage $allPackage)
    {
        abort_if(Gate::denies('all_package_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $allPackage->update($request->all());

        if ($request->input('package_image', false)) {
            if (! $allPackage->package_image || $request->input('package_image') !== $allPackage->package_image->file_name) {
                if ($allPackage->package_image) {
                    $allPackage->package_image->delete();
                }
                $allPackage->addMedia(storage_path('tmp/uploads/'.basename($request->input('package_image'))))->toMediaCollection('package_image');
            }
        } elseif ($allPackage->package_image) {
            $allPackage->package_image->delete();
        }

        return (new AllPackageResource($allPackage))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function storeMedia(Request $request)
    {
        abort_if(Gate::denies('all_package_create') && Gate::denies('all_package_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return $this->traitStoreMedia($request);
    }
}
