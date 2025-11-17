<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ResourceFile;
use Illuminate\Http\Request;
use App\Http\Resources\ResourceFileResource;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
        $per = (int)$request->query('per_page',12);
        $items = ResourceFile::orderBy('created_at','desc')->paginate($per);
        return ResourceFileResource::collection($items)->response();
    }

    public function show($id) { return new ResourceFileResource(ResourceFile::findOrFail($id)); }
}
