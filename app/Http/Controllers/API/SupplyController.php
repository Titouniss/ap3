<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\Supply;
use App\Models\Task;
use App\Models\Company;
use App\Models\TasksSupply;
use Monolog\Logger;
use Illuminate\Support\Facades\Auth;
use Monolog\Handler\StreamHandler;
class SupplyController extends BaseApiController
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
        parent::__construct(Supply::class);

    }
    protected function filterIndexQuery(Request $request, $query)
    {
        if ($request->has('company_id')) {
            $item = Company::find($request->company_id);
            if (!$item) {
                throw new ApiException("Paramètre 'company_id' n'est pas valide.");
            }
            $query->where('supplies.company_id', $request->company_id);
        }
    
        if ($request->has('task_id')) {
            $item = Task::find($request->task_id);
            if (!$item) {
                throw new ApiException("Paramètre 'task_id' n'est pas valide.");
            }

            $query->whereIn('id', $item->supplies->pluck('id'));
        }
    }
    protected function storeItem(array $arrayRequest)
    {
        return Supply::create([
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
        $item->save();


        return $item;
    }
    protected function updateTaskSupplyReceived(Request $request)
    {
        $arrayRequest = $request->all();
        $tasksupplies =  TasksSupply::where('task_id', $arrayRequest['task_id'])->get();
        $task = Task::where('id', $tasksupplies)->with('workarea', 'skills', 'comments', 'previousTasks', 'documents', 'project', 'periods', 'supplies')->get();
        foreach ($tasksupplies as $tasksupply) {
                $tasksupply->update(
                [
                'received' => $arrayRequest['received'],
                'status' => $arrayRequest['status'],
                ]);  
                $tasksupply->save();
           }
           return $this->successResponse($task,'Chargement terminé avec succès.');
    }
}
