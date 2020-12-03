<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class BaseModule extends Model
{
    protected $fillable = ['name', 'modulable_id', 'modulable_type', 'last_synced_at', 'is_active', 'company_id'];
    protected $appends = ['type'];

    public function getTypeAttribute()
    {
        return $this->modulable_type === SqlModule::class ? "sql" : "api";
    }

    public function modulable()
    {
        return $this->morphTo();
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id', 'id')->withTrashed();
    }

    public function moduleDataTypes()
    {
        return $this->hasMany(ModuleDataType::class, 'module_id', 'id');
    }

    public function sortedModuleDataTypes()
    {
        return $this->moduleDataTypes->load('dataType', 'moduleDataRows')->sortBy(function ($mdt) {
            return $mdt->dataType->order;
        });
    }

    public function hasModuleDataTypeForSlug($slug)
    {
        return $this->sortedModuleDataTypes()->first(function ($mdt) use ($slug) {
            return $mdt->dataType->slug === $slug;
        });
    }

    public function sync()
    {
        try {
            foreach ($this->sortedModuleDataTypes() as $mdt) {
                DB::beginTransaction();

                $dataType = $mdt->dataType;
                $table = app($dataType->model);

                foreach ($this->modulable->getRows($mdt) as $row) {
                    $data = array_filter(get_object_vars($row), function ($key) {
                        return $key !== "id";
                    }, ARRAY_FILTER_USE_KEY);
                    $oldId = ModelHasOldId::firstOrNew(['old_id' => $row->id, 'model' => $dataType->model, 'company_id' => $this->company_id]);
                    if (isset($oldId->new_id)) {
                        if ($model = $table->find($oldId->new_id)) {
                            $model->update($data);
                        } else {
                            ModelHasOldId::where([
                                'old_id' => $row->id,
                                'model' => $dataType->model,
                                'company_id' => $this->company_id
                            ])->update(['new_id' => $table->create($data)->id]);
                        }
                    } else {
                        ModelHasOldId::create([
                            'old_id' => $row->id,
                            'model' => $dataType->model,
                            'company_id' => $this->company_id,
                            'new_id' => $table->create($data)->id
                        ]);
                    }
                }

                DB::commit();
            }

            $this->last_synced_at = Carbon::now();
            $this->save();
            return true;
        } catch (\Throwable $th) {
            echo $th->getMessage() . "\n\r";
            DB::rollBack();
            $controllerLog = new Logger('BaseModule');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::ERROR);
            $controllerLog->error('BaseModule', [$th->getMessage()]);
            return false;
        }
    }
}
