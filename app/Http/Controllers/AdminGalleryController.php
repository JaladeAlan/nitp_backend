<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGalleryRequest;
use App\Http\Resources\GalleryResource;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use DB;

class AdminGalleryController extends Controller
{
    public function index(Request $request)
    {
        $per = (int) $request->query('per_page', 24);
        $items = Gallery::orderBy('created_at', 'desc')->paginate($per);

        return GalleryResource::collection($items);
    }

    public function store(StoreGalleryRequest $request)
    {
        DB::beginTransaction();
        try {
            $path = $request->file('image')->store('gallery', 'public');

            $item = Gallery::create([
                'title'   => $request->title,
                'caption' => $request->caption,
                'image'   => $path,
            ]);

            DB::commit();
            Log::info('Admin uploaded gallery image', ['admin' => auth('api')->id(), 'gallery' => $item->id]);

            return new GalleryResource($item);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gallery upload failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to upload image'], 500);
        }
    }

    public function show($id)
    {
        $item = Gallery::findOrFail($id);
        return new GalleryResource($item);
    }

    public function destroy($id)
    {
        $item = Gallery::findOrFail($id);
        if ($item->image) Storage::disk('public')->delete($item->image);
        $item->delete();

        Log::info('Admin deleted gallery item', ['admin' => auth('api')->id(), 'gallery' => $id]);
        return response()->json(['success' => true, 'message' => 'Gallery item deleted']);
    }
}
