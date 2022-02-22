<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\CompanyDetails;
use App\Models\Subscription;
use Carbon\Carbon;

use App\Models\Skill;
use App\Models\Customer;
use App\Models\Workarea;
use App\Models\Range;
use App\Models\Role;
use App\Models\DealingHours;
use App\Models\Unavailability;
use App\Models\Project;
use App\Models\TasksBundle;
use App\Models\WorkHours;
use App\Models\Hours;
use App\Models\Task;
use App\User;


use Illuminate\Support\Facades\Validator;

class CompanyController extends BaseApiController
{
    protected static $index_load = [];
    protected static $index_append = ['active_subscription'];
    protected static $show_load = ['skills:skills.id,name,company_id', 'subscriptions', 'subscriptions.packages'];
    protected static $show_append = [
        'active_subscription',
        'siret',
        'code',
        'type',
        'contact_firstname',
        'contact_lastname',
        'contact_function',
        'contact_tel1',
        'contact_tel2',
        'contact_email',
        'street_number',
        'street_name',
        'postal_code',
        'city',
        'country',
        'authorize_supply',
    ];

    protected static $store_validation_array = [
        'name' => 'required',
        'siret' => 'required',
        'code' => 'nullable',
        'type' => 'nullable',
        'contact_firstname' => 'required',
        'contact_lastname' => 'required',
        'contact_function' => 'required',
        'contact_email' => 'required|email',
        'contact_tel1' => 'nullable',
        'contact_tel2' => 'nullable',
        'street_number' => 'required',
        'street_name' => 'required',
        'postal_code' => 'required',
        'city' => 'required',
        'country' => 'required',
        'subscription' => 'required',
        'authorize_supply' =>'required'

    ];

    protected static $update_validation_array = [
        'name' => 'required',
        'siret' => 'required',
        'code' => 'nullable',
        'type' => 'nullable',
        'contact_firstname' => 'required',
        'contact_lastname' => 'required',
        'contact_function' => 'required',
        'contact_email' => 'required|email',
        'contact_tel1' => 'nullable',
        'contact_tel2' => 'nullable',
        'street_number' => 'required',
        'street_name' => 'required',
        'postal_code' => 'required',
        'city' => 'required',
        'country' => 'required',
        'authorize_supply' =>'required'

    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct(Company::class);
    }

    protected function filterIndexQuery(Request $request, $query)
    {
        $user = Auth::user();
        if (!$user->is_admin) {
            $query->where('companies.id', $user->company_id);
        }
        if (!$request->has('order_by')) {
            $query->leftJoin('subscriptions', function ($join) {
                $join->on('companies.id', '=', 'subscriptions.company_id')
                    ->where('subscriptions.state', 'active');
            })->groupBy('companies.id');

            $query->orderBy('subscriptions.ends_at', 'desc');
        }
    }

    protected function storeItem(array $arrayRequest)
    {
        if (!isset($arrayRequest['contact_tel1']) && !isset($arrayRequest['contact_tel2'])) {
            throw new ApiException('Au moins un numéro de téléphone est obligatoire.');
        }

        if (CompanyDetails::where('detailable_type', Company::class)->where('siret', $arrayRequest['siret'])->exists()) {
            throw new ApiException('Siret déjà utilisé par une autre sociéte.');
        }

        $item = Company::create([
            'name' => $arrayRequest['name'],
        ]);
        $item->details()->update([
            'siret' => $arrayRequest['siret'],
            'code' => $arrayRequest['code'] ?? null,
            'type' => $arrayRequest['type'] ?? null,
            'contact_firstname' => $arrayRequest['contact_firstname'],
            'contact_lastname' => $arrayRequest['contact_lastname'],
            'contact_function' => $arrayRequest['contact_function'],
            'contact_email' => $arrayRequest['contact_email'],
            'contact_tel1' => $arrayRequest['contact_tel1'],
            'contact_tel2' => $arrayRequest['contact_tel2'],
            'street_number' => $arrayRequest['street_number'],
            'street_name' => $arrayRequest['street_name'],
            'postal_code' => $arrayRequest['postal_code'],
            'city' => $arrayRequest['city'],
            'country' => $arrayRequest['country'],
            'authorize_supply' => $arrayRequest['authorize_supply'],
        ]);

        $subscriptionArray = $arrayRequest['subscription'];
        $validator = Validator::make($subscriptionArray, [
            'starts_at' => 'required',
            'ends_at' => 'required',
            'packages' => 'required',
            'is_trial' => 'required'
        ]);
        if ($validator->fails()) {
            throw new ApiException($validator->errors());
        }

        $subscription = Subscription::create([
            'company_id' => $item->id,
            'is_trial' => $subscriptionArray['is_trial']
        ]);

        try {
            $subscription->starts_at = Carbon::createFromFormat('d/m/Y H:i:s', $subscriptionArray['starts_at'] . ' 00:00:00');
        } catch (\Throwable $th) {
            throw new ApiException("Paramètre 'subscription.starts_at' doit être du format 'd/m/Y'.");
        }
        try {
            $subscription->ends_at = Carbon::createFromFormat('d/m/Y H:i:s', $subscriptionArray['ends_at'] . ' 23:59:59');
        } catch (\Throwable $th) {
            throw new ApiException("Paramètre 'subscription.ends_at' doit être du format 'd/m/Y'.");
        }
        try {
            $subscription->packages()->sync($subscriptionArray['packages']);
        } catch (\Throwable $th) {
            throw new ApiException("Paramètre 'subscription.packages' contient des valeurs invalides.");
        }

        //statut annulé par défaut
        $subscription->state = 'cancelled';
        $subscription->save();

        return $item;
    }

    protected function updateItem($item, array $arrayRequest)
    {
        if (!isset($arrayRequest['contact_tel1']) && !isset($arrayRequest['contact_tel2'])) {
            throw new ApiException('Au moins un numéro de téléphone est obligatoire.');
        }

        if ($arrayRequest['siret'] != $item->details->siret && CompanyDetails::where('detailable_type', Company::class)->where('siret', $arrayRequest['siret'])->exists()) {
            throw new ApiException('Siret déjà utilisé par une autre sociéte.');
        }

        $item->update([
            'name' => $arrayRequest['name'],
        ]);

        $item->details()->update([
            'siret' => $arrayRequest['siret'],
            'code' => $arrayRequest['code'] ?? null,
            'type' => $arrayRequest['type'] ?? null,
            'contact_firstname' => $arrayRequest['contact_firstname'],
            'contact_lastname' => $arrayRequest['contact_lastname'],
            'contact_function' => $arrayRequest['contact_function'],
            'contact_email' => $arrayRequest['contact_email'],
            'contact_tel1' => $arrayRequest['contact_tel1'],
            'contact_tel2' => $arrayRequest['contact_tel2'],
            'street_number' => $arrayRequest['street_number'],
            'street_name' => $arrayRequest['street_name'],
            'postal_code' => $arrayRequest['postal_code'],
            'city' => $arrayRequest['city'],
            'country' => $arrayRequest['country'],
            'authorize_supply' => $arrayRequest['authorize_supply'],

        ]);

        return $item;
    }

    protected function duplicate(Request $request){

        $arrayRequest = $request->all();

        foreach($arrayRequest['ids'] as $id){

            try {
                // Company
                $company = Company::withTrashed()->find($id);
                if(isset($company)){
                    $newCompany = $company->replicate();
                    $newCompany->name = $newCompany->name. '('.Company::where('name', 'LIKE', '%'.$company->name.'%')->count().')';
                    $newCompany->save();

                    // Company details
                    $companyDetails = CompanyDetails::where('detailable_id', $id)->first();
                    if($companyDetails){
                        $newCompanyDetails = $companyDetails->replicate();
                        $newCompanyDetails->detailable_id = $newCompany->id;
                        $newCompanyDetails->save();
                    }
                    

                    // Subcriptions
                    $subscriptions = Subscription::where('company_id', $id)->withTrashed()->get();
                    if(isset($subscriptions)){
                        foreach($subscriptions as $subscription){
                            $newSubscription = $subscription->replicate();
                            $newSubscription->company_id = $newCompany->id;
                            $newSubscription->save();

                            //Packages
                            $subscriptionHasPackages = DB::table('subscription_has_packages')->where('subscription_id', $subscription->id)->pluck('package_id');
                            if(isset($subscriptionHasPackages)){
                                $newSubscription->packages()->sync($subscriptionHasPackages);
                                $newSubscription->save();
                            } 
                        }
                    }

                    // Skills
                    $skills = Skill::where('company_id', $id)->withTrashed()->get();
                    if(isset($skills)){
                        foreach($skills as $skill){
                            $newSkill = $skill->replicate();
                            $newSkill->company_id = $newCompany->id;
                            $newSkill->save(); 
                        }
                    }

                    // Customers
                    $oldNewCustomer = [];
                    $customers = Customer::where('company_id', $id)->withTrashed()->get();
                    if(isset($customers)){
                        foreach($customers as $customer){
                            $newCustomer = $customer->replicate();
                            $newCustomer->company_id = $newCompany->id;
                            $newCustomer->save();

                            $oldNewCustomer[$customer->id] = $newCustomer->id;

                            //Customer details
                            $customerDetails = CompanyDetails::where('detailable_id', $id)->first();
                            if(isset($customerDetails)){
                                $newCustomerDetails = $customerDetails->replicate();
                                $newCustomerDetails->detailable_id = $newCustomer->id;
                                $newCustomerDetails->save();
                            }
                            
                        }
                    }

                    // Workareas
                    $oldNewWorkarea = [];
                    $workareas = Workarea::where('company_id', $id)->withTrashed()->get();
                    if(isset($workareas)){
                        foreach($workareas as $workarea){
                            $newWorkarea = $workarea->replicate();
                            $newWorkarea->company_id = $newCompany->id;
                            $newWorkarea->save();

                            $oldNewWorkarea[$workarea->id] = $newWorkarea->id;

                            //WorkareaSkills
                            foreach($workarea->skills as $skill){
                                $newWorkarea->skills()->sync(Skill::where('name', $skill->name)->where('company_id', $newCompany->id)->withTrashed()->pluck('id'));
                            }
                            $newWorkarea->save();
                        }
                    }

                    // Ranges
                    $ranges = Range::where('company_id', $id)->withTrashed()->get();
                    if(isset($ranges)){
                        foreach($ranges as $range){
                            $newRange = $range->replicate();
                            $newRange->company_id = $newCompany->id;
                            $newRange->save();

                            //RepetitiveTask
                            foreach($range->repetitive_tasks() as $repetitiveTask){

                                $newRepetitiveTask = $repetitiveTask->replicate();
                                $newRepetitiveTask->range_id = $newRange->id;
                                $newRepetitiveTask->save();
                                
                                //RepetitiveTaskSkills
                                foreach($repetitiveTask->skills as $skill){
                                    $newRepetitiveTask->skills()->sync(Skill::where('name', $skill->name)->where('company_id', $newCompany->id)->withTrashed()->pluck('id'));
                                }
                                $newRepetitiveTask->save();
                            }
                        }
                    }

                    //Roles
                    $roles = Role::where('company_id', $id)->withTrashed()->get();
                    if(isset($roles)){
                        foreach($roles as $role){
                            $newRole = $role->replicate();
                            $newRole->company_id = $newCompany->id;
                            $newRole->save();

                            //Permissions
                            $permissions = [];
                            foreach($role->permissions as $permission){
                                $permissions[] = $permission->id;
                            }
                            $newRole->syncPermissions($permissions);
                        }
                    }
                    
                    //Users
                    $oldNewUser = [];
                    $users = User::where('company_id', $id)->withTrashed()->get();
                    if(isset($users)){
                        foreach($users as $user){
                            $newUser = $user->replicate();
                            $newUser->company_id = $newCompany->id;
                            $newUser->login = $this->getRandomString(8);
                            $newUser->email = $this->getRandomString(6) . '@' . $this->getRandomString(5) . '.fr';
                            $newUser->register_token = '';
                            $newUser->save();

                            $oldNewUser[$user->id] = $newUser->id;

                            //UserRoles
                            if($user->role){
                                if($user->role->is_public){
                                    $newUser->syncRoles($user->role->id);
                                    $newUser->save();
                                }
                                else{
                                    $newUser->syncRoles(Role::where('name', $user->role->name)->where('company_id', $newCompany->id)->withTrashed()->pluck('id'));
                                    $newUser->save();
                                }
                            }

                            //UserSkills
                            foreach($user->skills as $skill){
                                $newUser->skills()->sync(Skill::where('name', $skill->name)->where('company_id', $newCompany->id)->withTrashed()->pluck('id'));
                            }
                            $newUser->save();

                            //WorkHours
                            $workHours = WorkHours::where('user_id', $user->id)->get();
                            foreach($workHours as $workHour){
                                $newWorkHour = $workHour->replicate();
                                $newWorkHour->user_id = $newUser->id;
                                $newWorkHour->save();
                            } 

                            //Dealing Hours
                            $dealingHours = DealingHours::where('user_id', $user->id)->get();
                            foreach($dealingHours as $dealingHour){
                                $newDealingHour = $dealingHour->replicate();
                                $newDealingHour->user_id = $newUser->id;
                                $newDealingHour->save();
                            } 

                            // Unavailabilities
                            $unavailabilities = Unavailability::where('user_id', $user->id)->get();
                            foreach($unavailabilities as $unavailability){
                                $newUnavailability = $unavailability->replicate();
                                $newUnavailability->user_id = $newUser->id;
                                $newUnavailability->save();
                            } 
                        }
                    }

                    //Project
                    $projects = Project::where('company_id', $company->id)->withTrashed()->get();
                    if(isset($projects)){
                        foreach($projects as $project){
                            $newProject = $project->replicate();
                            $newProject->company_id = $newCompany->id;
                            $newProject->customer_id = $project->customer_id ? $oldNewCustomer[$project->customer_id] : null;
                            $newProject->save();

                            // Task Bundle
                            $oldNewTasksBundle = [];
                            $tasks_bundle = TasksBundle::where('project_id', $project->id)->withTrashed()->get();
                            foreach($tasks_bundle as $task_bundle){
                                $newTaskBundle = $task_bundle->replicate();
                                $newTaskBundle->project_id = $newProject->id;
                                $newTaskBundle->company_id = $newCompany->id;
                                $newTaskBundle->save();

                                $oldNewTasksBundle[$task_bundle->id] = $newTaskBundle->id;
                            }

                            // Task 
                            $oldNewTask = [];
                            if(isset($project->tasks)){
                                foreach($project->tasks as $task){
                                    $newTask = $task->replicate();
                                    $newTask->tasks_bundle_id = $oldNewTasksBundle[$task->tasks_bundle_id];
                                    $newTask->workarea_id = $task->workarea_id ? $oldNewWorkarea[$task->workarea_id] : null;
                                    $newTask->created_by = isset($oldNewUser[$task->created_by]) ? $oldNewUser[$task->created_by] : $task->created_by;
                                    $newTask->user_id = $task->user_id ? $oldNewUser[$task->user_id] : null;
                                    unset($newTask->laravel_through_key);
                                    $newTask->save();

                                    $oldNewTask[$task->id] = $newTask->id;

                                    // Task Skill
                                    foreach($task->skills as $skill){
                                        $newTask->skills()->sync(Skill::where('name', $skill->name)->where('company_id', $newCompany->id)->withTrashed()->pluck('id'));
                                    }
                                    $newTask->save();

                                    //Task Comment
                                    foreach($task->comments as $comment){
                                        $newComment = $comment->replicate();
                                        $newComment->task_id = $newTask->id;
                                        $newComment->created_by = isset($oldNewUser[$comment->created_by]) ? $oldNewUser[$comment->created_by] : $comment->created_by;
                                        $newComment->save();
                                    }

                                    // Task Time Spent
                                    foreach($task->taskTimeSpent as $timeSpent){
                                        $newTimeSpent = $timeSpent->replicate();
                                        $newTimeSpent->user_id = $oldNewUser[$timeSpent->user_id];
                                        $newTimeSpent->task_id = $newTask->id;
                                        $newTimeSpent->save();
                                    }

                                    //Task Period
                                    foreach($task->periods as $period){
                                        $newPeriod = $period->replicate();
                                        $newPeriod->task_id = $newTask->id;
                                        $newPeriod->save();
                                    }
                                }

                                foreach($project->tasks as $task){
                                    // Previous Task
                                    foreach($task->previousTasks as $previousTask){
                                        $newPreviousTask = $previousTask->replicate();
                                        $newPreviousTask->task_id = $oldNewTask[$previousTask->task_id];
                                        $newPreviousTask->previous_task_id = $oldNewTask[$previousTask->previous_task_id];
                                        $newPreviousTask->save();
                                    }
                                }
                            }

                            // Hours
                            $hours = Hours::where('project_id', $project->id)->get();
                            if(isset($hours)){
                                foreach($hours as $hour){
                                    $newHour = $hour->replicate();
                                    $newHour->user_id = $oldNewUser[$hour->user_id];
                                    $newHour->project_id = $newProject->id;
                                    $newHour->save();
                                }
                            }
                        }
                    }

                    if (static::$show_load) {
                        $newCompany->load(static::$show_load);
                    }
        
                    if (static::$show_append) {
                        $newCompany->append(static::$show_append);
                    }

                    return $this->successResponse($newCompany, 'Duplication terminé avec succès.');
                }

            } catch (\Throwable $th) {
                throw new ApiException($th);
            }
        }
    }

    private function getRandomString($n) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
      
        for ($i = 0; $i < $n; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }
      
        return $randomString;
    }
}
