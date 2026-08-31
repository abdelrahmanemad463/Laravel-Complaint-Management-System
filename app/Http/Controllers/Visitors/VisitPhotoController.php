<?php

namespace App\Http\Controllers\Visitors;

use App\Http\Controllers\Controller;
use App\Http\Requests\Visitors\StoreVisitPhotoRequest;
use App\Models\VisitorVisitItem;
use App\Models\VisitorVisitPhoto;
use App\Services\Visitors\VisitorPhotoService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitPhotoController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private VisitorPhotoService $photos) {}

    public function store(StoreVisitPhotoRequest $request, VisitorVisitItem $visitItem): JsonResponse
    {
        $this->authorize('update', $visitItem->visit);

        try {
            $data = $this->photos->store($request->file('photo'));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $photo = $visitItem->photos()->create(array_merge($data, [
            'visit_id' => $visitItem->visit_id,
        ]));

        return response()->json([
            'ok' => true,
            'photo' => [
                'id' => $photo->id,
                'url' => route('visitors.photos.serve', $photo),
                'original_name' => $photo->original_name,
                'compressed_size' => $photo->compressed_size,
            ],
        ]);
    }

    /**
     * Serve an evidence photo through an authenticated, authorized route.
     * The photo disk is private; it is never publicly linked.
     */
    public function serve(VisitorVisitPhoto $photo): StreamedResponse
    {
        $this->authorize('view', $photo->visit);
        return Storage::disk('local')->download($photo->path, $photo->original_name);
    }
}
