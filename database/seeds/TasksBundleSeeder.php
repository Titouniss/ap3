<?php

use Illuminate\Database\Seeder;
use App\Models\TasksBundle;

class TasksBundleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {   
        $projects = DB::table('projects')->get();
        foreach ($projects as $key => $project) {
            $exist = TasksBundle::where('project_id', $project->id)->first();
            if (!$exist) {
                TasksBundle::create(['company_id' => $project->company_id, 'project_id' => $project->id]);
            }
        }
    }
}
