<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProjectRequest;
use App\Http\Requests\Admin\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use DB;

class AdminProjectController extends Controller
{
    public function index(Request $request)
    {
        $per = (int) $request->query('per_page', 12);
        $projects = Project::orderBy('created_at', 'desc')->paginate($per);

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request)
    {
        DB::beginTransaction();
        try {
            $coverPath = null;
            if ($request->hasFile('cover')) {
                $coverPath = $request->file('cover')->store('projects', 'public');
            }

            $project = Project::create([
                'title'     => $request->title,
                'summary'   => $request->summary,
                'body'      => $request->body,
                'cover'     => $coverPath,
                'published' => $request->boolean('published', false),
                'slug'      => Str::slug($request->title) . '-' . Str::random(6),
            ]);

            DB::commit();
            Log::info('Admin created project', ['admin' => auth('api')->id(), 'project' => $project->id]);

            return new ProjectResource($project);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create project', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to create project'], 500);
        }
    }

    public function show($id)
    {
        $project = Project::findOrFail($id);
        return new ProjectResource($project);
    }

    public function update(UpdateProjectRequest $request, $id)
    {
        $project = Project::findOrFail($id);

        DB::beginTransaction();
        try {
            if ($request->hasFile('cover')) {
                if ($project->cover) {
                    Storage::disk('public')->delete($project->cover);
                }
                $project->cover = $request->file('cover')->store('projects', 'public');
            }

            if ($request->filled('title')) $project->title = $request->title;
            if ($request->filled('summary')) $project->summary = $request->summary;
            if ($request->filled('body')) $project->body = $request->body;
            if ($request->has('published')) $project->published = $request->boolean('published');

            // Optionally update slug if title changed
            if ($request->filled('title')) {
                $project->slug = Str::slug($request->title) . '-' . Str::random(6);
            }

            $project->save();
            DB::commit();

            Log::info('Admin updated project', ['admin' => auth('api')->id(), 'project' => $project->id]);
            return new ProjectResource($project);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update project', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to update project'], 500);
        }
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);

        if ($project->cover) {
            Storage::disk('public')->delete($project->cover);
        }

        $project->delete();
        Log::info('Admin deleted project', ['admin' => auth('api')->id(), 'project' => $id]);

        return response()->json(['success' => true, 'message' => 'Project deleted']);
    }
}
