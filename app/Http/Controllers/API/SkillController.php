<?php

namespace App\Http\Controllers\API;

use App\Models\Skill;
use App\Models\TasksSkill;

class SkillController extends BaseApiControllerWithSoftDelete
{
    protected static $company_id_field = 'company_id';
    protected static $index_with = ['company:id,name'];
    protected static $show_load = ['company:id,name'];
    protected static $show_append = null;
    protected static $cascade = false;

    protected static $store_validation_array = [
        'name' => 'required',
        'company_id' => 'required'
    ];

    protected static $update_validation_array = [
        'name' => 'required',
        'company_id' => 'required'
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct(Skill::class);
    }

    protected function storeItem(array $arrayRequest)
    {
        return Skill::create([
            'name' => $arrayRequest['name'],
            'company_id' => $arrayRequest['company_id'],
        ]);
    }

    protected function updateItem($item, array $arrayRequest)
    {
        $item->update([
            'name' => $arrayRequest['name'],
            'company_id' => $arrayRequest['company_id']
        ]);
        return $item;
    }

    /**
     * getting skills by task.
     */
    public function getByTaskId(int $id)
    {
        $item = app($this->model)->find($id);
        if ($result = $this->checkItemErrors($item, 'show')) {
            return $result;
        }

        $items = TasksSkill::select('skill_id')->where('task_id', $id)->get();
        return $this->successResponse($items);
    }
}
