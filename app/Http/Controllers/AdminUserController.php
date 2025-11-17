<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePartnerRequest;
use App\Http\Requests\Admin\UpdatePartnerRequest;
use App\Http\Resources\PartnerResource;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use DB;

class AdminPartnerController extends Controller
{
    public function index(Request $request)
    {
        $per = (int) $request->query('per_page', 20);
        $items = Partner::orderBy('name')->paginate($per);

        return PartnerResource::collection($items);
    }

    public function store(StorePartnerRequest $request)
    {
        DB::beginTransaction();
        try {
            $logo = $request->hasFile('logo') ? $request->file('logo')->store('partners', 'public') : null;

            $p = Partner::create([
                'name'    => $request->name,
                'website' => $request->website,
                'logo'    => $logo,
            ]);

            DB::commit();
            Log::info('Admin added partner', ['admin' => auth('api')->id(), 'partner' => $p->id]);

            return new PartnerResource($p);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Partner create failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to add partner'], 500);
        }
    }

    public function show($id)
    {
        $p = Partner::findOrFail($id);
        return new PartnerResource($p);
    }

    public function update(UpdatePartnerRequest $request, $id)
    {
        $p = Partner::findOrFail($id);

        DB::beginTransaction();
        try {
            if ($request->hasFile('logo')) {
                if ($p->logo) Storage::disk('public')->delete($p->logo);
                $p->logo = $request->file('logo')->store('partners', 'public');
            }

            if ($request->filled('name')) $p->name = $request->name;
            if ($request->filled('website')) $p->website = $request->website;

            $p->save();
            DB::commit();

            Log::info('Admin updated partner', ['admin' => auth('api')->id(), 'partner' => $p->id]);
            return new PartnerResource($p);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Partner update failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to update partner'], 500);
        }
    }

    public function destroy($id)
    {
        $p = Partner::findOrFail($id);
        if ($p->logo) Storage::disk('public')->delete($p->logo);
        $p->delete();

        Log::info('Admin deleted partner', ['admin' => auth('api')->id(), 'partner' => $id]);
        return response()->json(['success' => true, 'message' => 'Partner deleted']);
    }
}
