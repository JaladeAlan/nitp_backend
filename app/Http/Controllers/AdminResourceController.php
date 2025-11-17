<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreResourceRequest;
use App\Http\Resources\ResourceFileResource;
use App\Models\ResourceFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use DB;

class AdminResourceController extends Controller
{
    public function index(Request $request)
    {
        $per = (int) $request->query('per_page', 20);
        $items = ResourceFile::orderBy('created_at', 'desc')->paginate($per);

        return ResourceFileResource::collection($items);
    }

    public function store(StoreResourceRequest $request)
    {
        DB::beginTransaction();
        try {
            $path = $request->file('file')->store('resources', 'public');

            $res = ResourceFile::create([
                'title'       => $request->title,
                'file'        => $path,
                'description' => $request->description,
            ]);

            DB::commit();
            Log::info('Admin uploaded resource', ['admin' => auth('api')->id(), 'resource' => $res->id]);

            return new ResourceFileResource($res);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Resource upload failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to upload resource'], 500);
        }
    }

    public function show($id)
    {
        $item = ResourceFile::findOrFail($id);
        return new ResourceFileResource($item);
    }

    public function destroy($id)
    {
        $item = ResourceFile::findOrFail($id);
        if ($item->file) Storage::disk('public')->delete($item->file);
        $item->delete();

        Log::info('Admin deleted resource', ['admin' => auth('api')->id(), 'resource' => $id]);
        return response()->json(['success' => true, 'message' => 'Resource deleted']);
    }
}
