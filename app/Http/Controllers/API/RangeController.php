<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiException;
use App\Models\Company;
use App\Models\Range;
use App\Models\RepetitiveTask;
use App\Models\RepetitiveTasksSkill;
use App\Traits\StoresDocuments;
use Illuminate\Http\Request;

class RangeController extends BaseApiController
{
    use StoresDocuments;

    protected static $index_load = ['company:companies.id,name'];
    protected static $index_append = null;
    protected static $show_load = ['company:companies.id,name', 'repetitive_tasks'];
    protected static $show_append = null;

    protected static $store_validation_array = [
        'name' => 'required',
        'description' => 'nullable',
        'company_id' => 'required',
        'repetitive_tasks' => 'required|array',
    ];

    protected static $update_validation_array = [
        'name' => 'required',
        'description' => 'nullable',
        'repetitive_tasks' => 'required|array',
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct(Range::class);
    }

    protected function filterIndexQuery(Request $request, $query)
    {
        if ($request->has('company_id')) {
            $item = Company::find($request->company_id);
            if (!$item) {
                throw new ApiException("Paramètre 'company_id' n'est pas valide.");
            }

            $query->where('ranges.company_id', $request->company_id);
        }
    }

    protected function storeItem(array $arrayRequest)
    {
        $repetitive_tasks = $arrayRequest['repetitive_tasks'];

        $item = Range::create([
            'name' => $arrayRequest['name'],
            'description' => $arrayRequest['description'],
            'company_id' => $arrayRequest['company_id'],
        ]);
        foreach ($repetitive_tasks as $repetitive_task) {
            $this->storeRepetitiveTask($item, $repetitive_task);
        }

        return $item;
    }

    protected function updateItem($item, array $arrayRequest)
    {
        $item->update([
            'name' => $arrayRequest['name'],
            'description' => $arrayRequest['description'],
            'company_id' => $arrayRequest['company_id'],
        ]);

        if (isset($arrayRequest['repetitive_tasks'])) {
            $ids = [];
            foreach ($arrayRequest['repetitive_tasks'] as $repetitive_task) {
                $task = $this->updateRepetitiveTask($item, $repetitive_task);
                array_push($ids, $task->id);
            }
            RepetitiveTask::whereNotIn('id', $ids)->where('range_id', $item->id)->delete();
        } else {
            RepetitiveTask::where('range_id', $item->id)->delete();
        }

        return $item;
    }

    /**
     * Gets the repetitive tasks of a range.
     */
    public function getRepetitiveTasks(int $id)
    {
        $item = app($this->model)->find($id);
        if ($error = $this->itemErrors($item, 'show')) {
            return $error;
        }

        $items = RepetitiveTask::where('range_id', $item->id)->with('skills', 'workarea', 'documents')->get();
        return $this->successResponse($items);
    }

    private function storeRepetitiveTask($range, $repetitive_task)
    {
        $repetitive_task['range_id'] = $range->id;
        $item = RepetitiveTask::create($repetitive_task);
        foreach ($repetitive_task['skills'] as $skill_id) {
            $this->storeRepetitiveTasksSkill($item->id, $skill_id);
        }

        if (isset($repetitive_task['token'])) {
            $this->storeDocumentsByToken($item, $repetitive_task['token'], $range->company);
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
                $this->deleteUnusedDocuments($item, $repetitive_task['documents']);
            }

            if (isset($repetitive_task['token'])) {
                $this->storeDocumentsByToken($item, $repetitive_task['token'], $range->company);
            }
        } else {
            $item = $this->storeRepetitiveTask($range, $repetitive_task);
        }

        return $item->load('documents');
    }
}
