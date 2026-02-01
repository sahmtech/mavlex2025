<?php

namespace App\Http\Controllers;

use App\Allergen;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AllergenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if (! auth()->user()->can('allergen.view')) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $allergens = Allergen::where('business_id', $business_id)->select(['id', 'name', 'icon']);

            return DataTables::of($allergens)
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-xs btn-primary edit-allergen"
                            data-id="' . $row->id . '"
                            data-name="' . e($row->name) . '"
                            data-icon="' . e($row->icon) . '"
                            data-href="' . action([self::class, 'update'], $row->id) . '">
                            <i class="fa fa-edit"></i>
                        </button>

                        <button data-href="' . action([self::class, 'destroy'], $row->id) . '"
                            class="btn btn-xs btn-danger delete-allergen">
                            <i class="fa fa-trash"></i>
                        </button>
                    ';
                })
                ->editColumn('icon', function ($row) {
                    return $row->icon;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('allergens.index');
    }

    /**
     * Store a newly created allergen.
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('allergen.create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:191|unique:allergens,name',
            'icon' => 'nullable|string|max:191',
        ]);

        $business_id = $request->session()->get('user.business_id');


        Allergen::create([
            'name' => $request->name,
            'icon' => $request->icon,
            'business_id' => $business_id
        ]);

        return response()->json([
            'success' => true,
            'msg' => __('messages.added_success')
        ]);
    }

    /**
     * Update the specified allergen.
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('allergen.edit')) {
            abort(403, 'Unauthorized action.');
        }

        $allergen = Allergen::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:191|unique:allergens,name,' . $allergen->id,
            'icon' => 'nullable|string|max:191',
        ]);

        $allergen->update([
            'name' => $request->name,
            'icon' => $request->icon,
        ]);

        return response()->json([
            'success' => true,
            'msg' => __('messages.updated_success')
        ]);
    }

    /**
     * Remove the specified allergen.
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('allergen.delete')) {
            abort(403, 'Unauthorized action.');
        }
        
        $allergen = Allergen::findOrFail($id);

        $allergen->delete();

        return response()->json([
            'success' => true,
            'msg' => __('messages.deleted_success')
        ]);
    }
}
