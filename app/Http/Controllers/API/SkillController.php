<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Skill;
use App\Models\TasksSkill;
use Exception;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Database\Eloquent\SoftDeletes;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class SkillController extends Controller
{
    use SoftDeletes;

    public $successStatus = 200;
    public $errorStatus = 400;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $items = [];
        if ($user->is_admin) {
            $items = Skill::withTrashed()->get()->load('company');
        } else if ($user->company_id != null) {
            $items = Skill::withTrashed()->where('company_id', $user->company_id)->get()->load('company');
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
        //
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
            'company_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $item = Skill::create($arrayRequest)->load('company');
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
            'name' => 'required',
            'company_id' => 'required'
        ]);

        $update = Skill::where('id', $id)->update(['name' => $arrayRequest['name'], 'company_id' => $arrayRequest['company_id']]);
        if ($update) {
            $item = Skill::find($id)->load('company');
            return response()->json(['success' => $item], $this->successStatus);
        } else {
            return response()->json(['errore' => 'error'], $this->errorStatus);
        }
    }

    /**
     * Restore the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        try {
            $item = Skill::withTrashed()->findOrFail($id);
            $success = $item->restore();

            if ($success) {
                return response()->json(['success' => $item->load('company')], $this->successStatus);
            } else {
                throw new Exception('Impossible de restaurer la compétence');
            }
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 400);
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
        $controllerLog = new Logger('skill');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('skill', ['destroy']);

        try {
            $item = Skill::findOrFail($id);
            $success = $item->delete();

            if ($success) {
                return response()->json(['success' => $item->load('company')], $this->successStatus);
            } else {
                throw new Exception('Impossible d\'archiver la compétence');
            }
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 400);
        }
    }

    /**
     * forceDelete the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function forceDelete($id)
    {
        $controllerLog = new Logger('skill');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('skill', ['forceDelete']);


        try {
            $item = Skill::withTrashed()->findOrFail($id);
            $success = $item->forceDelete();

            if ($success) {
                return response()->json(['success' => true], $this->successStatus);
            } else {
                throw new Exception('Impossible de supprimer la compétence');
            }
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 400);
        }
    }

    /**
     * getting skills by task.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getByTaskId(int $task_id)
    {
        $items = TasksSkill::select('skill_id')->where('task_id', $task_id)->get();

        if ($items) {
            return response()->json(['success' => $items], $this->successStatus);
        } else {
            return response()->json(['errore' => 'error'], $this->errorStatus);
        }
    }
}
