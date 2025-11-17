<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardStatsResource;
use App\Models\User;
use App\Models\News;
use App\Models\Event;
use App\Models\Project;
use App\Models\Gallery;
use App\Models\ResourceFile;
use App\Models\Partner;

class AdminDashboardController extends Controller
{
    public function stats()
    {
        $stats = [
            'users'      => User::count(),
            'news'       => News::count(),
            'events'     => Event::count(),
            'projects'   => Project::count(),
            'gallery'    => Gallery::count(),
            'resources'  => ResourceFile::count(),
            'partners'   => Partner::count(),
        ];

        return new DashboardStatsResource($stats);
    }
}
