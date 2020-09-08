<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use stdClass;

class SqlModule extends BaseModule
{
    protected $fillable = ['driver', 'host', 'port', 'charset', 'database', 'username', 'password'];

    public $timestamps = false;

    public function module()
    {
        return $this->morphOne(BaseModule::class, 'modulable');
    }

    public function connectionData()
    {
        $data = [
            'driver' => $this->driver,
            'host' => $this->host,
            'charset' => $this->charset,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
        ];

        if ($this->driver !== 'sqlite') {
            $port = $this->port;
            if (!$port) {
                switch ($this->driver) {
                    case 'pgsql':
                        $port = '5432';
                        break;
                    case 'sqlsrv':
                        $port = '1433';
                        break;
                    default: // MySQL
                        $port = '3306';
                        break;
                }
            }
            $data['port'] = $port;
        }
        return $data;
    }

    public function getData()
    {
        $data = [];
        try {
            Config::set('database.connections.' . $this->module->name, $this->connectionData());
            DB::purge($this->module->name);
            foreach ($this->module->sortedModuleDataTypes() as $mdt) {
                $query = DB::connection($this->module->name)->table($mdt->source);
                foreach ($mdt->moduleDataRows as $mdr) {
                    $query->selectRaw($mdr->source . ' AS ' . $mdr->dataRow->field);
                }
                $results = $query->get()->map(function ($result) use ($mdt) {
                    $object = new stdClass();
                    if ($mdt->dataType->slug !== "unavailabilities") {
                        $object->company_id = $this->module->company_id;
                    }
                    foreach (get_object_vars($result) as $key => $value) {
                        $dataRow = DataRow::where('data_type_id', $mdt->data_type_id)->where('field', $key)->firstOrFail();
                        $mdr = ModuleDataRow::where('module_data_type_id', $mdt->id)->where('data_row_id', $dataRow->id)->firstOrFail();
                        $newValue = $value ?? $mdr->default_value;
                        if ($newValue) {
                            $details = null;
                            if ($mdr->details) {
                                $details = json_decode($mdr->details);
                            }
                            switch ($dataRow->type) {
                                case 'integer':
                                    $newValue = intval($newValue);
                                    break;
                                case 'datetime':
                                    $newValue = new Carbon($newValue);
                                    break;
                                case 'enum':
                                    if ($details && $details->options) {
                                        $newValue = $details->options->{$newValue} ?? $mdr->default_value;
                                    }
                                    break;
                                case 'relationship':
                                    if ($details && $details->model) {
                                        $newValue = ModelHasOldId::where('model', $details->model)->where('old_id', $result->id)->firstOrFail()->id;
                                    }
                                    break;
                                default: // String
                                    if ($details && $details->max_length) {
                                        $newValue = substr($newValue, 0, intval($details->max_length));
                                    }
                                    break;
                            }
                        }
                        $object->{$key} = $newValue;
                    }
                    return $object;
                });

                $data[$mdt->dataType->slug] = $results;
            }
        } catch (\Throwable $th) {
            $data = [];
            echo $th->getMessage();
            $controllerLog = new Logger('SQLModule');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
            $controllerLog->info('SQLModule', [$th->getMessage()]);
        }
        return $data;
    }
}
