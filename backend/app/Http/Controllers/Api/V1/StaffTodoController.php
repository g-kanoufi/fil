<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreStaffTodoRequest;
use App\Http\Requests\Api\V1\UpdateStaffTodoRequest;
use App\Http\Resources\Api\V1\StaffTodoResource;
use App\Models\StaffTodo;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class StaffTodoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StaffTodo::class);

        $query = StaffTodo::query()->with(['assignee', 'author'])->orderByRaw('completed_at IS NOT NULL')->orderBy('due_at');

        if ($request->query('completed') === '1') {
            $query->whereNotNull('completed_at');
        } elseif ($request->query('completed') === '0') {
            $query->whereNull('completed_at');
        }

        if ($request->query('assignee') === 'me') {
            $query->where('assignee_user_id', $request->user()?->id);
        }

        return ApiResponse::collection(
            StaffTodoResource::collection($query->limit(100)->get()),
        );
    }

    public function store(StoreStaffTodoRequest $request): JsonResponse
    {
        $this->authorize('create', StaffTodo::class);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $todo = StaffTodo::query()->create([
            'title' => trim($request->string('title')->toString()),
            'body' => $request->input('body'),
            'assignee_user_id' => $request->input('assignee_user_id'),
            'author_user_id' => $user->id,
            'subject_type' => $request->input('subject_type'),
            'subject_id' => $request->input('subject_id'),
            'due_at' => $request->input('due_at'),
        ]);

        $todo->load(['assignee', 'author']);

        return ApiResponse::resource(new StaffTodoResource($todo), 201);
    }

    public function update(UpdateStaffTodoRequest $request, StaffTodo $staffTodo): JsonResponse
    {
        $this->authorize('update', $staffTodo);

        if ($request->has('title')) {
            $staffTodo->title = trim($request->string('title')->toString());
        }

        if ($request->has('body')) {
            $staffTodo->body = $request->input('body');
        }

        if ($request->has('assignee_user_id')) {
            $staffTodo->assignee_user_id = $request->input('assignee_user_id');
        }

        if ($request->has('due_at')) {
            $staffTodo->due_at = $request->input('due_at');
        }

        if ($request->has('subject_type')) {
            $staffTodo->subject_type = $request->input('subject_type');
            $staffTodo->subject_id = $request->input('subject_id');
        }

        if ($request->has('completed')) {
            $staffTodo->completed_at = $request->boolean('completed') ? now() : null;
        }

        $staffTodo->save();
        $staffTodo->load(['assignee', 'author']);

        return ApiResponse::resource(new StaffTodoResource($staffTodo));
    }

    public function destroy(StaffTodo $staffTodo): Response
    {
        $this->authorize('delete', $staffTodo);
        $staffTodo->delete();

        return response()->noContent();
    }
}
