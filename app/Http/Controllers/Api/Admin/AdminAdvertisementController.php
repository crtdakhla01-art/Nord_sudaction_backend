<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdvertisementRequest;
use App\Http\Requests\UpdateAdvertisementRequest;
use App\Models\Advertisement;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class AdminAdvertisementController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Advertisement::query()->latest('begin_date')->get()
        );
    }

    public function store(StoreAdvertisementRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['image'] = $request->file('image')->store('advertisements', 'public');

        $advertisement = Advertisement::query()->create($data);

        return response()->json($advertisement, 201);
    }

    public function show(Advertisement $advertisement): JsonResponse
    {
        return response()->json($advertisement);
    }

    public function update(UpdateAdvertisementRequest $request, Advertisement $advertisement): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $newImage = $request->file('image')->store('advertisements', 'public');

            if (!empty($advertisement->image)) {
                Storage::disk('public')->delete($advertisement->image);
            }

            $data['image'] = $newImage;
        }

        $advertisement->update($data);

        return response()->json($advertisement->fresh());
    }

    public function destroy(Advertisement $advertisement): JsonResponse
    {
        if (!empty($advertisement->image)) {
            Storage::disk('public')->delete($advertisement->image);
        }

        $advertisement->delete();

        return response()->json([
            'message' => 'Advertisement deleted successfully.',
        ]);
    }
}
