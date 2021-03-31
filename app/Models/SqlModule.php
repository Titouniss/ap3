<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use stdClass;

class SqlModule extends Model
{
    protected $fillable = ['driver', 'host', 'port', 'charset', 'database', 'username', 'password'];
    protected $hidden = ['connection'];

    public $timestamps = false;

    public function module()
    {
        return $this->morphOne(BaseModule::class, 'modulable');
    }

    public function getConnectionDataAttribute($includePassword = false)
    {
        $data = [
            'id' => $this->id,
            'driver' => $this->driver ?? "",
            'host' => $this->host ?? "",
            'port' => $this->port ?? "",
            'charset' => $this->charset ?? "",
            'database' => $this->database ?? "",
            'username' => $this->username ?? "",
        ];

        if ($includePassword) {
            $data['password'] = $this->password;
        } else {
            $data['has_password'] = $this->password !== null;
        }

        return $data;
    }

    public function getRows(ModuleDataType $mdt)
    {
        $rows = [];
        $lowestUniqueId = 0;
        try {
            Config::set('database.connections.' . $this->module->name, $this->getConnectionDataAttribute(true));
            DB::purge($this->module->name);

            $query = DB::connection($this->module->name)->table($mdt->source);
            $onlyDefaultValueRows = [];
            foreach ($mdt->moduleDataRows as $mdr) {
                if ($mdr->source) {
                    $query->selectRaw($mdr->source . ' AS field_' . $mdr->dataRow->field);
                } else {
                    // Set default value directly without getting from source
                    array_push($onlyDefaultValueRows, $mdr);
                }
            }

            foreach ($query->get() as $result) {
                $row = new stdClass();
                if (!in_array($mdt->dataType->slug, ["unavailabilities", "tasks", "task_time_spent"])) {
                    $row->company_id = $this->module->company_id;
                }

                try {
                    foreach (get_object_vars($result) as $k => $value) {
                        $key = str_replace('field_', "", $k);
                        $dataRow = DataRow::where('data_type_id', $mdt->data_type_id)->where('field', $key)->firstOrFail();
                        $mdr = ModuleDataRow::where('module_data_type_id', $mdt->id)->where('data_row_id', $dataRow->id)->firstOrFail();
                        $details = json_decode($mdr->details);

                        $newValue = $value ?? $mdr->default_value;
                        if ($details && isset($details->only_if_null)) {
                            if (
                                $details->only_if_null
                                && $currentModel = app($mdr->moduleDataType->dataType->model)->find(
                                    ModelHasOldId::firstOrNew([
                                        'company_id' => $this->module->company_id,
                                        'model' => $mdr->moduleDataType->dataType->model,
                                        'old_id' => $result->field_id
                                    ])->new_id
                                )
                            ) {
                                $newValue = $currentModel->{$mdr->dataRow->field};
                            } else {
                                $newValue = $mdr->applyDetailsToValue($newValue, $lowestUniqueId);
                            }
                        } else {
                            $newValue = $mdr->applyDetailsToValue($newValue, $lowestUniqueId);
                        }
                        $row->{$key} = $newValue;
                    }

                    foreach ($onlyDefaultValueRows as $mdr) {
                        $row->{$mdr->dataRow->field} = $mdr->applyDetailsToValue($mdr->default_value, $lowestUniqueId);
                    }

                    array_push($rows, $row);
                } catch (\Throwable $th) {
                    echo $th->getMessage() . "\n\r";
                    $controllerLog = new Logger('SQLModule');
                    $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::ERROR);
                    $controllerLog->error('SQLModule', [$th->getMessage()]);
                }
            }
        } catch (\Throwable $th) {
            $rows = [];
            echo $th->getMessage() . "\n\r";
            $controllerLog = new Logger('SQLModule');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::ERROR);
            $controllerLog->error('SQLModule', [$th->getMessage()]);
        }

        return $rows;
    }
}
