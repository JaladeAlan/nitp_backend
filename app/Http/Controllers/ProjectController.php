<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Http\Resources\ProjectResource;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $per = (int)$request->query('per_page',12);
        $items = Project::orderBy('created_at','desc')->paginate($per);
        return ProjectResource::collection($items)->response();
    }

    public function show($id) { return new ProjectResource(Project::findOrFail($id)); }
}
