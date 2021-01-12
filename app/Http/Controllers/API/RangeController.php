<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\ModelHasDocuments;
use App\Models\Range;
use App\Models\RepetitiveTask;
use App\Models\RepetitiveTasksSkill;
use Exception;
use Spatie\Permission\Models\Role;

use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class RangeController extends Controller
{
    use SoftDeletes;

    public $successStatus = 200;

    /**
     * list of items api
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $listObject = [];
        if ($user->is_admin) {
            $listObject = Range::withTrashed()->get()->load('company');
        } else if ($user->company_id != null) {
            $listObject = Range::withTrashed()->where('company_id', $user->company_id)->get()->load('company');
        }
        return response()->json(['success' => $listObject], $this->successStatus);
    }

    /**
     * get single item api
     *
     * @param \App\Models\Range $range
     * @return \Illuminate\Http\Response
     */
    public function show(Range $range)
    {
        return response()->json(['success' => $range], $this->successStatus);
    }

    /**
     * create item api
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'name' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $repetitive_tasks = $arrayRequest['repetitive_tasks'];

        if ($user != null) {
            $item = Range::create($arrayRequest);
            if ($item != null) {
                if (isset($repetitive_tasks)) {
                    foreach ($repetitive_tasks as $repetitive_task) {
                        $this->storeRepetitiveTask($item, $repetitive_task);
                    }
                }
            }
            return response()->json(['success' => $item], $this->successStatus);
        }

        return response()->json(['success' => 'notAuthentified'], 500);
    }

    /**
     * update item api
     *
     * @param \App\Models\Range $range
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Range $range)
    {
        $arrayRequest = $request->all();

        $validator = Validator::make($arrayRequest, [
            'name' => 'required'
        ]);

        $range->update([
            'name' => $arrayRequest['name'],
            'description' => $arrayRequest['description'],
        ]);

        if (isset($arrayRequest['repetitive_tasks'])) {
            $ids = [];
            foreach ($arrayRequest['repetitive_tasks'] as $repetitive_task) {
                $task = $this->updateRepetitiveTask($range, $repetitive_task);
                array_push($ids, $task->id);
            }
            RepetitiveTask::whereNotIn('id', $ids)->where('range_id', $range->id)->delete();
        } else {
            RepetitiveTask::where('range_id', $range->id)->delete();
        }
        return response()->json(['success' => true, 'item' => $range], $this->successStatus);
    }

    /**
     * Restore the specified resource in storage.
     *
     * @param \App\Models\Range $range
     * @return \Illuminate\Http\Response
     */
    public function restore(Range $range)
    {
        if (!$range->restoreCascade()) {
            return response()->json(['error' => 'Erreur lors de la restauration'], 400);
        }

        return response()->json(['success' => $range], $this->successStatus);
    }

    /**
     * Archive the specified resource from storage.
     *
     * @param \App\Models\Range $range
     * @return \Illuminate\Http\Response
     */
    public function destroy(Range $range)
    {
        if (!$range->deleteCascade()) {
            return response()->json(['error' => 'Erreur lors de l\'archivage'], 400);
        }

        return response()->json(['success' => $range], $this->successStatus);
    }

    /**
     * forceDelete the specified resource from storage.
     *
     * @param \App\Models\Range $range
     * @return \Illuminate\Http\Response
     */
    public function forceDelete(Range $range)
    {
        if (!$range->forceDelete()) {
            return response()->json(['error' => 'Erreur lors de la suppression'], 400);
        }

        return response()->json(['success' => true], $this->successStatus);
    }

    /**
     * Gets the repetitive tasks of a range.
     *
     * @param \App\Models\Range $range
     * @return \Illuminate\Http\Response
     */
    public function getRepetitiveTasks(Range $range)
    {
        $items = RepetitiveTask::where('range_id', $range->id)->with('skills', 'workarea', 'documents')->get();
        return response()->json(['success' => $items], $this->successStatus);
    }

    private function storeDocuments(int $task_id, $token, $company)
    {
        if ($token && $task_id) {
            $documents = Document::where('token', $token)->get();

            foreach ($documents as $doc) {
                ModelHasDocuments::firstOrCreate(['model' => RepetitiveTask::class, 'model_id' => $task_id, 'document_id' => $doc->id]);
                $doc->moveFile($company->name . '/ranges');
                $doc->token = null;
                $doc->save();
            }
        }
    }

    private function storeRepetitiveTask($range, $repetitive_task)
    {
        $repetitive_task['range_id'] = $range->id;
        $item = RepetitiveTask::create($repetitive_task);
        foreach ($repetitive_task['skills'] as $skill_id) {
            $this->storeRepetitiveTasksSkill($item->id, $skill_id);
        }

        if (isset($repetitive_task['token'])) {
            $this->storeDocuments($item->id, $repetitive_task['token'], $range->company);
        }

        return $item;
    }

    private function storeRepetitiveTasksSkill($repetitive_task_id, $skill_id)
    {
        RepetitiveTasksSkill::create(['repetitive_task_id' => $repetitive_task_id, 'skill_id' => $skill_id]);
    }

    private function updateRepetitiveTask($range, $repetitive_task)
    {
        $item = null;
        if (isset($repetitive_task['id'])) {
            $item = RepetitiveTask::findOrFail($repetitive_task['id']);
            $item->name = $repetitive_task['name'];
            $item->order = $repetitive_task['order'];
            $item->description = $repetitive_task['description'];
            $item->estimated_time = $repetitive_task['estimated_time'];
            $item->workarea_id = $repetitive_task['workarea_id'];
            $item->save();

            RepetitiveTasksSkill::where('repetitive_task_id', $item->id)->delete();
            foreach ($repetitive_task['skills'] as $skill_id) {
                $this->storeRepetitiveTasksSkill($item->id, $skill_id);
            }

            if (isset($repetitive_task['documents'])) {
                $documents = $item->documents()->whereNotIn('id', array_map(function ($doc) {
                    return $doc['id'];
                }, $repetitive_task['documents']))->get();

                foreach ($documents as $doc) {
                    $doc->deleteFile();
                }

                $item->load('documents');
            }

            if (isset($repetitive_task['token'])) {
                $this->storeDocuments($item->id, $repetitive_task['token'], $range->company);
            }
        } else {
            $item = $this->storeRepetitiveTask($range, $repetitive_task);
        }

        return $item;
    }
}
