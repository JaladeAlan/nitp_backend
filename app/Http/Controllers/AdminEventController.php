<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Http\Requests\Admin\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Support\Facades\Storage;
use DB, Log;

class AdminEventController extends Controller
{
    public function index() {
        $per = request()->query('per_page', 20);
        return EventResource::collection(Event::orderBy('start_date','desc')->paginate($per));
    }

    public function store(StoreEventRequest $request) {
        DB::beginTransaction();
        try {
            $banner = $request->hasFile('banner') ? $request->file('banner')->store('events','public') : null;
            $event = Event::create($request->only(['title','description','start_date','end_date','location']) + ['banner'=>$banner]);
            DB::commit();
            return new EventResource($event);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Event create failed', ['err'=>$e->getMessage()]);
            return response()->json(['success'=>false,'message'=>'Failed to create event'],500);
        }
    }

    public function show($id) { return new EventResource(Event::findOrFail($id)); }

    public function update(UpdateEventRequest $request, $id)
    {
        $event = Event::findOrFail($id);

        DB::beginTransaction();
        try {
            // Handle banner upload
            if ($request->hasFile('banner')) {
                if ($event->banner) {
                    Storage::disk('public')->delete($event->banner);
                }
                $event->banner = $request->file('banner')->store('events','public');
            }

            // Update other fields
            $event->title = $request->input('title', $event->title);
            $event->description = $request->input('description', $event->description);
            $event->location = $request->input('location', $event->location);
            $event->start_date = $request->input('start_date', $event->start_date);
            $event->end_date = $request->input('end_date', $event->end_date);

            $event->save();
            DB::commit();

            return new EventResource($event);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Event update failed', ['err'=>$e->getMessage()]);
            return response()->json(['success'=>false,'message'=>'Failed to update event'],500);
        }
    }


    public function destroy($id) {
        $event = Event::findOrFail($id);
        if ($event->banner) Storage::disk('public')->delete($event->banner);
        $event->delete();
        return response()->json(['success'=>true,'message'=>'Deleted']);
    }
}
