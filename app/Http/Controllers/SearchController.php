<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Event;
use App\Models\News;

class SearchController extends Controller
{
    /**
     * Search projects, events, and news by query.
     */
    public function index(Request $request)
    {
        $query = $request->query('query');

        if (!$query) {
            return response()->json([
                'data' => [],
                'message' => 'No search query provided'
            ]);
        }

        // Search Projects
        $projects = Project::where('title', 'like', "%{$query}%")
            ->orWhere('summary', 'like', "%{$query}%")
            ->orWhere('body', 'like', "%{$query}%")
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'type' => 'project',
                    'title' => $project->title,
                    'summary' => $project->summary,
                    'cover' => $project->cover ? asset('storage/' . $project->cover) : null,
                ];
            });

        // Search Events
        $events = Event::where('title', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")  
            ->orWhere('location', 'like', "%{$query}%")
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'type' => 'event',
                    'title' => $event->title,
                    'description' => $event->description,
                    'location' => $event->location,
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date,
                    'banner' => $event->banner ? asset('storage/' . $event->banner) : null,
                ];
            });

        // Search News
        $news = News::where('title', 'like', "%{$query}%")
            ->orWhere('content', 'like', "%{$query}%")
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => 'news',
                    'title' => $item->title,
                    'summary' => $item->content,
                    'image' => $item->image ? asset('storage/'.$item->image) : null,
                ];
            });

        // Merge all results
        $results = $projects->concat($events)->concat($news);

        return response()->json([
            'data' => $results,
            'total' => $results->count(),
        ]);
    }
}
