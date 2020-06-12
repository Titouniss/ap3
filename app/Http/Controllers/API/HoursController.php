<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hours;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Validator;


class HoursController extends Controller
{
    public $successStatus = 200;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $items = Hours::where('user_id', $user->id);
        if ($user->hasRole('superAdmin')) {
            $items = Hours::select('*');
            if ($request->user_id) {
                $items->where('user_id', $request->user_id);
            }
        }
        if ($request->project_id) {
            $items->where('project_id', $request->project_id);
        }

        if ($request->date) {
            switch ($request->period_type) {
                case 'week':
                    $items->whereBetween('date', [Carbon::createFromFormat('d-m-Y', $request->date)->startOfWeek(), Carbon::createFromFormat('d-m-Y', $request->date)->endOfWeek()]);
                    break;
                case 'month':
                    $items->whereMonth('date', Carbon::createFromFormat('d-m-Y', $request->date)->month);
                    break;
                case 'year':
                    $items->whereYear('date', Carbon::createFromFormat('d-m-Y', $request->date)->year);
                    break;

                default:
                    $items->whereDate('date', Carbon::createFromFormat('d-m-Y', $request->date));
                    break;
            }
        }

        return response()->json(['success' => $items->with('project', 'user')->get(), 'sql' => $items->toSql()], $this->successStatus);
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $item = Hours::where('id', $id)->with('project', 'user')->first();
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
            'user_id' => 'required',
            'project_id' => 'required',
            'date' => 'required',
            'duration' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $item = Hours::create($arrayRequest);

        return response()->json(['success' => $item], $this->successStatus);
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
            'user_id' => 'required',
            'project_id' => 'required',
            'date' => 'required',
            'duration' => 'required'
        ]);

        $update = Hours::where('id', $id)
            ->update([
                'user_id' => $arrayRequest['user_id'],
                'project_id' => $arrayRequest['project_id'],
                'date' => $arrayRequest['date'],
                'duration' => $arrayRequest['duration'],
                'description' => $arrayRequest['description'],
            ]);

        if ($update) {
            $item = Hours::find($id);
            return response()->json(['success' => $item], $this->successStatus);
        } else {
            return response()->json(['error' => 'error'], $this->errorStatus);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $item = Hours::findOrFail($id);
        $item->delete();
        return '';
    }
}
