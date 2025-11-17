<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;
use App\Http\Resources\GalleryResource;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $per = (int)$request->query('per_page',24);
        $items = Gallery::orderBy('created_at','desc')->paginate($per);
        return GalleryResource::collection($items)->response();
    }

    public function show($id) { return new GalleryResource(Gallery::findOrFail($id)); }
}
