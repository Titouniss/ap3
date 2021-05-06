<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TasksBundle;

class ProjectObserver
{
    /**
     * Handle the project "created" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function created(Project $project)
    {
        //
    }

    /**
     * Handle the project "updated" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function updated(Project $project)
    {
        //
    }

    /**
     * Handle the project "deleted" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function deleted(Project $project)
    {
        foreach ($project->tasksBundles as $t) {
            $tasks = Task::where('tasks_bundle_id', $t->id)->get();
            foreach ($tasks as $task) {
                $task->delete();
            }
        }

        $taskBundles = TasksBundle::where('project_id', $project->id)->get();
        foreach ($taskBundles as $taskBundle) {
            $taskBundle->delete();
        }
    }

    /**
     * Handle the project "restored" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function restored(Project $project)
    {
        $taskBundles = TasksBundle::withTrashed()->where('project_id', $project->id)->get();
        foreach ($taskBundles as $taskBundle) {
            $taskBundle->restore();
        }
        $taskBundle=TasksBundle::where('project_id',$project->id)->get();
        $taskBundleId=$taskBundle[0]['id'];
        $tasks = Task::withTrashed()->where('tasks_bundle_id', $taskBundleId)->get();
        foreach ($tasks as $task) {
            $task->restore();
        }
    }

    /**
     * Handle the project "force deleted" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function forceDeleted(Project $project)
    {
        //
    }
}
