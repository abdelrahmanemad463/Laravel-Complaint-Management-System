<?php

namespace App\Http\Controllers\Visitors;

use App\Http\Controllers\Controller;
use App\Http\Requests\Visitors\StoreVisitPhotoRequest;
use App\Models\VisitorCapaAction;
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

        $evidenceRole = $request->input('evidence_role', 'initial');
        $capaActionId = $request->input('capa_action_id');

        // Resolution evidence must reference the linked violation on this item.
        if ($evidenceRole === 'resolution') {
            if (!$capaActionId || $capaActionId !== $visitItem->linked_capa_action_id) {
                abort(422, 'Invalid capa_action_id for resolution evidence.');
            }
        }

        try {
            $data = $this->photos->store($request->file('photo'));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $photo = $visitItem->photos()->create(array_merge($data, [
            'visit_id' => $visitItem->visit_id,
            'capa_action_id' => $capaActionId,
            'evidence_role' => $evidenceRole,
        ]));

        return response()->json([
            'ok' => true,
            'photo' => [
                'id' => $photo->id,
                'url' => route('visitors.photos.serve', $photo),
                'original_name' => $photo->original_name,
                'compressed_size' => $photo->compressed_size,
                'evidence_role' => $photo->evidence_role,
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