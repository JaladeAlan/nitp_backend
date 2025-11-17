<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Http\Resources\PartnerResource;

class PartnerController extends Controller
{
    public function index()
    {
        $partners = Partner::orderBy('name')->get();
        return PartnerResource::collection($partners);
    }
}
