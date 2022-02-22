<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiException;
use App\Models\Company;
use App\Models\Skill;
use App\Models\Task;
use App\Models\Workarea;
use Illuminate\Http\Request;

class SkillController extends BaseApiController
{
    protected static $index_load = ['company:companies.id,name'];
    protected static $index_append = null;
    protected static $show_load = ['company:companies.id,name'];
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

    protected function filterIndexQuery(Request $request, $query)
    {
        if ($request->has('company_id')) {
            $item = Company::find($request->company_id);
            if (!$item) {
                throw new ApiException("Paramètre 'company_id' n'est pas valide.");
            }

            $query->where('skills.company_id', $request->company_id);
        }

        if ($request->has('workarea_id')) {
            if (Workarea::where('id', $request->workarea_id)->doesntExist()) {
                throw new ApiException("Paramètre 'workarea_id' n'est pas valide.");
            }

            $query->join('workareas_skills', function ($join) use ($request) {
                $join->on('workareas_skills.skill_id', '=', 'skills.id')
                    ->where('workareas_skills.workarea_id', $request->workarea_id);
            });
        }

        if ($request->has('task_id')) {
            $item = Task::find($request->task_id);
            if (!$item) {
                throw new ApiException("Paramètre 'task_id' n'est pas valide.");
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
