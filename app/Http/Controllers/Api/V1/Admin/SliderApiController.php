<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Controllers\Traits\ResponseTrait;
use App\Http\Requests\StoreSliderRequest;
use App\Http\Requests\UpdateSliderRequest;
use App\Http\Resources\Admin\SliderResource;
use App\Models\Slider;
use Gate;
use Symfony\Component\HttpFoundation\Response;

class SliderApiController extends Controller
{
    use MediaUploadingTrait,ResponseTrait;

    public function index()
    {
        abort_if(Gate::denies('slider_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new SliderResource(Slider::all());
    }

    public function store(StoreSliderRequest $request)
    {
        $slider = Slider::create($request->all());

        foreach ($request->input('image', []) as $file) {
            $slider->addMedia(storage_path('tmp/uploads/'.basename($file)))->toMediaCollection('image');
        }

        return (new SliderResource($slider))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Slider $slider)
    {
        abort_if(Gate::denies('slider_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new SliderResource($slider);
    }

    public function update(UpdateSliderRequest $request, Slider $slider)
    {
        $slider->update($request->all());

        if (count($slider->image) > 0) {
            foreach ($slider->image as $media) {
                if (! in_array($media->file_name, $request->input('image', []))) {
                    $media->delete();
                }
            }
        }
        $media = $slider->image->pluck('file_name')->toArray();
        foreach ($request->input('image', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $slider->addMedia(storage_path('tmp/uploads/'.basename($file)))->toMediaCollection('image');
            }
        }

        return (new SliderResource($slider))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Slider $slider)
    {
        abort_if(Gate::denies('slider_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $slider->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }

    // //////////// API ////////////

    public function sliders()
    {

        $data = Slider::where('status', '1')->get()->map(function ($slider) {
            return [
                'id' => $slider->id,
                'heading' => $slider->heading,
                'image' => $slider->image[0]->getUrl(), // Adjust based on your actual relationship
            ];
        })->toArray();

        return response()->json([
            'status' => 200,
            'message' => trans('global.slider_data'),
            'data' => $data,
        ]);

    }
}
