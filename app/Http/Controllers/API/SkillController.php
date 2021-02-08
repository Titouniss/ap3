<?php

namespace App\Http\Controllers\API;

use App\Models\Skill;
use App\Models\Task;
use Exception;
use Illuminate\Http\Request;

class SkillController extends BaseApiController
{
    protected static $index_load = ['company:id,name'];
    protected static $index_append = null;
    protected static $show_load = ['company:id,name'];
    protected static $show_append = null;

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

    protected function filterIndexQuery($query, Request $request)
    {
        if ($request->has('task_id')) {
            $item = Task::find($request->task_id);
            if (!$item) {
                throw new Exception("Paramètre 'task_id' n'est pas valide.");
            }

            $query->whereIn('id', $item->skills->pluck('id'));
        }
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
}
