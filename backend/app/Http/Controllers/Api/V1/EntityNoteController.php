<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEntityNoteRequest;
use App\Http\Requests\Api\V1\UpdateEntityNoteRequest;
use App\Http\Resources\Api\V1\EntityNoteResource;
use App\Models\EntityNote;
use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use App\Services\Activity\ActivityRecorder;
use App\Services\Operations\EntityNoteAccessService;
use App\Services\Operations\EntityNoteSubjectResolver;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class EntityNoteController extends Controller
{
    public function indexForLead(
        Lead $lead,
        EntityNoteSubjectResolver $subjects,
        EntityNoteAccessService $access,
    ): JsonResponse {
        $this->authorize('view', $lead);

        return $this->index('lead', $subjects->subjectId($lead), $subjects, $access);
    }

    public function storeForLead(
        StoreEntityNoteRequest $request,
        Lead $lead,
        EntityNoteSubjectResolver $subjects,
        EntityNoteAccessService $access,
        ActivityRecorder $activity,
    ): JsonResponse {
        $this->authorize('view', $lead);

        return $this->store($request, 'lead', $subjects->subjectId($lead), $subjects, $access, $activity);
    }

    public function indexForStore(
        Store $store,
        EntityNoteSubjectResolver $subjects,
        EntityNoteAccessService $access,
    ): JsonResponse {
        $this->authorize('view', $store);

        return $this->index('store', $subjects->subjectId($store), $subjects, $access);
    }

    public function storeForStore(
        StoreEntityNoteRequest $request,
        Store $store,
        EntityNoteSubjectResolver $subjects,
        EntityNoteAccessService $access,
        ActivityRecorder $activity,
    ): JsonResponse {
        $this->authorize('view', $store);

        return $this->store($request, 'store', $subjects->subjectId($store), $subjects, $access, $activity);
    }

    public function indexForContact(
        User $contact,
        EntityNoteSubjectResolver $subjects,
        EntityNoteAccessService $access,
    ): JsonResponse {
        $this->authorize('viewContact', $contact);

        return $this->index('contact', $subjects->subjectId($contact), $subjects, $access);
    }

    public function storeForContact(
        StoreEntityNoteRequest $request,
        User $contact,
        EntityNoteSubjectResolver $subjects,
        EntityNoteAccessService $access,
        ActivityRecorder $activity,
    ): JsonResponse {
        $this->authorize('viewContact', $contact);

        return $this->store($request, 'contact', $subjects->subjectId($contact), $subjects, $access, $activity);
    }

    public function update(
        UpdateEntityNoteRequest $request,
        EntityNote $entityNote,
        EntityNoteSubjectResolver $subjects,
        ActivityRecorder $activity,
    ): JsonResponse {
        $this->authorize('update', $entityNote);

        if ($request->has('body')) {
            $entityNote->body = trim($request->string('body')->toString());
        }

        if ($request->has('is_private')) {
            $entityNote->is_private = $request->boolean('is_private');
        }

        $entityNote->save();
        $entityNote->load('author');

        $subject = $subjects->resolve($entityNote->subject_type, (int) $entityNote->subject_id);
        $actor = $request->user();
        $activity->record(
            category: $subjects->activityCategory($entityNote->subject_type),
            action: 'updated',
            summary: sprintf('%s updated a corp note', $actor?->name ?? 'Staff'),
            actor: $actor,
            subject: $subject,
            payload: ['entity_note_id' => $entityNote->id],
        );

        return ApiResponse::resource(new EntityNoteResource($entityNote));
    }

    public function destroy(
        EntityNote $entityNote,
        EntityNoteSubjectResolver $subjects,
        ActivityRecorder $activity,
    ): Response {
        $this->authorize('delete', $entityNote);

        $subject = $subjects->resolve($entityNote->subject_type, (int) $entityNote->subject_id);
        $actor = request()->user();
        $noteId = $entityNote->id;
        $entityNote->delete();

        $activity->record(
            category: $subjects->activityCategory($entityNote->subject_type),
            action: 'deleted',
            summary: sprintf('%s deleted a corp note', $actor?->name ?? 'Staff'),
            actor: $actor,
            subject: $subject,
            payload: ['entity_note_id' => $noteId],
        );

        return response()->noContent();
    }

    private function index(
        string $type,
        int $subjectId,
        EntityNoteSubjectResolver $subjects,
        EntityNoteAccessService $access,
    ): JsonResponse {
        $subject = $subjects->resolve($type, $subjectId);
        $subjects->authorizeView(request()->user(), $subject);

        $entityKey = $subjects->entityKey($subject);
        $user = request()->user();
        abort_unless($user !== null && $access->canViewNotes($user, $entityKey), 403);

        $notes = EntityNote::query()
            ->with('author')
            ->where('subject_type', $type)
            ->where('subject_id', $subjectId)
            ->orderByDesc('created_at')
            ->get();

        $visible = $access->filterVisible($notes, $user, $entityKey);

        return ApiResponse::collection(EntityNoteResource::collection($visible));
    }

    private function store(
        StoreEntityNoteRequest $request,
        string $type,
        int $subjectId,
        EntityNoteSubjectResolver $subjects,
        EntityNoteAccessService $access,
        ActivityRecorder $activity,
    ): JsonResponse {
        $subject = $subjects->resolve($type, $subjectId);
        $subjects->authorizeView($request->user(), $subject);

        $entityKey = $subjects->entityKey($subject);
        $user = $request->user();
        abort_unless($user !== null && $access->canCreateNote($user, $entityKey, $request->isPrivate()), 403);

        $note = EntityNote::query()->create([
            'subject_type' => $type,
            'subject_id' => $subjectId,
            'author_user_id' => $user->id,
            'body' => $request->bodyText(),
            'is_private' => $request->isPrivate(),
        ]);

        $note->load('author');

        $activity->record(
            category: $subjects->activityCategory($type),
            action: 'created',
            summary: sprintf('%s added a corp note', $user->name),
            actor: $user,
            subject: $subject,
            payload: ['entity_note_id' => $note->id, 'is_private' => $note->is_private],
        );

        return ApiResponse::resource(new EntityNoteResource($note), 201);
    }
}
