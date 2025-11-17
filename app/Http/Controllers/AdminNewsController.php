<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreNewsRequest;
use App\Http\Requests\Admin\UpdateNewsRequest;
use App\Http\Resources\NewsResource;
use App\Models\News;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use DB, Log;

class AdminNewsController extends Controller
{
    public function index()
    {
        $per = request()->query('per_page', 20);
        $items = News::orderBy('published_at','desc')->paginate($per);
        return NewsResource::collection($items);
    }

    public function store(StoreNewsRequest $request)
    {
        DB::beginTransaction();
        try {
            $image = $request->hasFile('image') ? $request->file('image')->store('news','public') : null;
            $slug = Str::slug($request->title).'-'.Str::random(6);
            $news = News::create([
                'title'=>$request->title,
                'slug'=>$slug,
                'body'=>$request->body,
                'image'=>$image,
                'published'=>$request->boolean('published', false),
                'published_at'=>$request->published ? ($request->published_at ?? now()) : null,
            ]);
            DB::commit();
            Log::info('News created', ['admin'=>auth('api')->id(),'news'=>$news->id]);
            return new NewsResource($news);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('News store failed', ['err'=>$e->getMessage()]);
            return response()->json(['success'=>false,'message'=>'Failed to create news'],500);
        }
    }

    public function show($id) { return new NewsResource(News::findOrFail($id)); }

    public function update(UpdateNewsRequest $request, $id)
    {
        $news = News::findOrFail($id);
        DB::beginTransaction();
        try {
            if ($request->hasFile('image')) {
                if ($news->image) Storage::disk('public')->delete($news->image);
                $news->image = $request->file('image')->store('news','public');
            }
            if ($request->filled('title')) $news->title = $request->title;
            if ($request->filled('body')) $news->body = $request->body;
            if ($request->has('published')) $news->published = $request->boolean('published');
            if ($request->filled('published_at')) $news->published_at = $request->published_at;
            $news->save();
            DB::commit();
            return new NewsResource($news);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('News update failed', ['err'=>$e->getMessage()]);
            return response()->json(['success'=>false,'message'=>'Failed to update news'],500);
        }
    }

    public function destroy($id)
    {
        $news = News::findOrFail($id);
        if ($news->image) Storage::disk('public')->delete($news->image);
        $news->delete();
        return response()->json(['success'=>true,'message'=>'Deleted']);
    }

}
