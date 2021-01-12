<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Skill;
use App\Models\Task;
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
     * @param  \App\Models\Skill  $skill
     * @return \Illuminate\Http\Response
     */
    public function show(Skill $skill)
    {
        return response()->json(['success' => $skill], $this->successStatus);
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
     * @param  \App\Models\Skill  $skill
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Skill $skill)
    {
        $arrayRequest = $request->all();

        $validator = Validator::make($arrayRequest, [
            'name' => 'required',
            'company_id' => 'required'
        ]);

        if (!$skill->update(['name' => $arrayRequest['name'], 'company_id' => $arrayRequest['company_id']])) {
            return response()->json(['errore' => 'error'], $this->errorStatus);
        }

        return response()->json(['success' => $skill->load('company')], $this->successStatus);
    }

    /**
     * Restore the specified resource in storage.
     *
     * @param  \App\Models\Skill  $skill
     * @return \Illuminate\Http\Response
     */
    public function restore(Skill $skill)
    {
        if (!$skill->restore()) {
            return response()->json(['success' => false, 'error' => 'Impossible de restaurer la compétence'], 400);
        }

        return response()->json(['success' => $skill->load('company')], $this->successStatus);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Skill  $skill
     * @return \Illuminate\Http\Response
     */
    public function destroy(Skill $skill)
    {
        $controllerLog = new Logger('skill');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('skill', ['destroy']);

        if (!$skill->delete()) {
            return response()->json(['success' => false, 'error' => 'Impossible d\'archiver la compétence'], 400);
        }

        return response()->json(['success' => $skill->load('company')], $this->successStatus);
    }

    /**
     * forceDelete the specified resource from storage.
     *
     * @param  \App\Models\Skill  $skill
     * @return \Illuminate\Http\Response
     */
    public function forceDelete(Skill $skill)
    {
        $controllerLog = new Logger('skill');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('skill', ['forceDelete']);

        if (!$skill->forceDelete()) {
            return response()->json(['success' => false, 'error' => 'Impossible de supprimer la compétence'], 400);
        }

        return response()->json(['success' => true], $this->successStatus);
    }

    /**
     * getting skills by task.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function getByTaskId(Task $task)
    {
        $items = TasksSkill::select('skill_id')->where('task_id', $task->id)->get();

        if ($items) {
            return response()->json(['success' => $items], $this->successStatus);
        } else {
            return response()->json(['errore' => 'error'], $this->errorStatus);
        }
    }
}
