<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use App\Http\Resources\NewsResource;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $per = (int)$request->query('per_page',12);
        $news = News::where('published', true)->orderBy('published_at','desc')->paginate($per);
        return NewsResource::collection($news)->response();
    }

    public function show($id)
    {
        $item = News::where('published',true)->findOrFail($id);
        return new NewsResource($item);
    }
}
