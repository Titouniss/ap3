<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workarea;
use App\Models\WorkareasSkill;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkareaController extends Controller
{
    use SoftDeletes;

    public $successStatus = 200;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $items = [];
        if ($user->hasRole('superAdmin')) {
            $items = Workarea::withTrashed()->get()->load('company', 'skills');
        } else if ($user->company_id != null) {
            $items = Workarea::withTrashed()->where('company_id', $user->company_id)->get()->load('company', 'skills');
        }
        return response()->json(['success' => $items], $this->successStatus);
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $item = Workarea::where('id', $id)->first()->load('skills', 'company');
        return response()->json(['success' => $item], $this->successStatus);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'name' => 'required',
            'company_id' => 'required',
            'max_users' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $item = Workarea::create($arrayRequest)->load('company');
        if (!empty($arrayRequest['skills'])) {
            foreach ($arrayRequest['skills'] as $skill_id) {
                WorkareasSkill::create(['workarea_id' => $item->id, 'skill_id' => $skill_id]);
            }
        }

        return response()->json(['success' => $item->load('skills')], $this->successStatus);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $arrayRequest = $request->all();

        $validator = Validator::make($arrayRequest, [
            'name' => 'required',
            'company_id' => 'required'
        ]);
        $item = Workarea::find($id);
        if ($item != null) {
            $item->name = $arrayRequest['name'];
            $item->company_id = $arrayRequest['company_id'];
            $item->max_users = $arrayRequest['max_users'];

            WorkareasSkill::where('workarea_id', $item->id)->delete();
            if (!empty($arrayRequest['skills'])) {
                foreach ($arrayRequest['skills'] as $skill_id) {
                    WorkareasSkill::create(['workarea_id' => $item->id, 'skill_id' => $skill_id]);
                }
            }
            $item->save();
        }
        $item = Workarea::find($id)->load('skills', 'company');
        return response()->json(['success' => $item], $this->successStatus);
    }

    /**
     * Restore the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        $item = Workarea::withTrashed()->findOrFail($id)->restore();
        if ($item) {
            $item = Workarea::where('id', $id)->first()->load('skills', 'company');
            return response()->json(['success' => $item], $this->successStatus);
        }
    }

    /**
     * Archive the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $item = Workarea::findOrFail($id);
        $item->delete();

        $item = Workarea::withTrashed()->where('id', $id)->first()->load('skills', 'company');
        return response()->json(['success' => $item], $this->successStatus);
    }

    /**
     * forceDelete the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function forceDelete($id)
    {
        $item = Workarea::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['success' => true], $this->successStatus);
    }
}
