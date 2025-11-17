<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use App\Http\Resources\EventResource;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $per = (int)$request->query('per_page',12);
        $items = Event::orderBy('start_date','desc')->paginate($per);
        return EventResource::collection($items)->response();
    }

    public function show($id)
    {
        return new EventResource(Event::findOrFail($id));
    }
}
