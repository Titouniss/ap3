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
            $data['port'] = $this->port;
        }

        return $data;
    }

    public function getModuleDataRows(ModuleDataType $mdt)
    {
        $rows = [];
        $lowestUniqueId = 0;
        try {
            Config::set('database.connections.' . $this->module->name, $this->connectionData());
            DB::purge($this->module->name);

            $query = DB::connection($this->module->name)->table($mdt->source);
            foreach ($mdt->moduleDataRows as $mdr) {
                if ($mdr->source) {
                    $query->selectRaw($mdr->source . ' AS ' . $mdr->dataRow->field);
                }
            }

            $rows = $query->get()->map(function ($result) use ($mdt, $lowestUniqueId) {
                $row = new stdClass();
                if (!in_array($mdt->dataType->slug, ["unavailabilities", "tasks"])) {
                    $row->company_id = $this->module->company_id;
                }
                $usedMdrIds = [];
                foreach (get_object_vars($result) as $key => $value) {
                    $dataRow = DataRow::where('data_type_id', $mdt->data_type_id)->where('field', $key)->firstOrFail();
                    $mdr = ModuleDataRow::where('module_data_type_id', $mdt->id)->where('data_row_id', $dataRow->id)->firstOrFail();
                    array_push($usedMdrIds, $mdr->id);
                    $newValue = $value ?? $mdr->default_value;
                    if ($newValue) {
                        $details = json_decode($mdr->details);
                        $drDetails = json_decode($dataRow->details);
                        switch ($dataRow->type) {
                            case 'integer':
                                $newValue = intval($newValue);
                                break;
                            case 'datetime':
                                if ($details && $details->format) {
                                    $newValue = Carbon::createFromFormat($details->format, $newValue);
                                } else {
                                    $newValue = new Carbon($newValue);
                                }
                                break;
                            case 'enum':
                                if ($details && $details->options) {
                                    $newValue = $details->options->{$newValue} ?? $mdr->default_value;
                                }
                                break;
                            case 'relationship':
                                if ($value && $drDetails && $drDetails->model) {
                                    $newValue = ModelHasOldId::where('model', $drDetails->model)->where('old_id', $value)->firstOr(function () {
                                        return new ModelHasOldId(); // Rendra new_id vide
                                    })->new_id;
                                }
                                break;
                            default: // String
                                if ($details) {
                                    if ($details->split) {
                                        $valueArray = explode($details->split->delimiter, $newValue);
                                        if ($details->split->keep == 'start') {
                                            $newValue = implode($details->split->delimiter, array_splice($valueArray, ceil(count($valueArray) / 2)));
                                        } else {
                                            $newValue = implode($details->split->delimiter, array_slice($valueArray, ceil(count($valueArray) / 2)));
                                        }
                                    }

                                    if ($details->format) {
                                        $formattedValue = "";
                                        switch ($details->format->prefix) {
                                            case 'company':
                                                $formattedValue .= strtolower($this->module->company->name) . $details->format->glue;
                                                break;

                                            default:
                                                break;
                                        }

                                        $valueArray = explode($details->format->delimiter, $newValue);
                                        switch ($details->format->case) {
                                            case 'lowerCamelCase':
                                                $formattedValue .= implode("", array_map(function ($v, $k) {
                                                    return $k ? ucfirst($v) : $v;
                                                }, $valueArray, array_keys($valueArray)));
                                                break;
                                            case 'upperCamelCase':
                                                $formattedValue .= implode("", array_map(function ($v) {
                                                    return ucfirst($v);
                                                }, $valueArray));
                                                break;

                                            default:
                                                break;
                                        }

                                        switch ($details->format->suffix) {
                                            case 'company':
                                                $formattedValue .= $details->format->glue . strtolower($this->module->company->name);
                                                break;

                                            default:
                                                break;
                                        }

                                        $newValue = $formattedValue;
                                    }
                                }

                                if ($drDetails) {
                                    if ($drDetails->max_length) {
                                        $newValue = substr($newValue, 0, intval($details->max_length));
                                    }
                                    if ($drDetails->is_password) {
                                        $newValue = bcrypt($newValue);
                                    }
                                    if ($drDetails->remove_special_chars) {
                                        $newValue = SqlModule::str_to_noaccent($newValue);
                                    }
                                    if ($drDetails->is_unique) {
                                        $uniqueValue = $newValue;
                                        while (app($mdt->dataType->model)->where($key, $uniqueValue)->exists()) {
                                            $uniqueValue = $newValue . ++$lowestUniqueId;
                                        }
                                        $newValue = $uniqueValue;
                                    }
                                }
                                break;
                        }
                    }
                    $row->{$key} = $newValue;
                }

                ModuleDataRow::where('module_data_type_id', $mdt->id)->whereNotIn('id', $usedMdrIds)->get()->each(function ($mdr) use ($row) {
                    $row->{$mdr->dataRow->field} = $mdr->default_value;
                });

                return $row;
            });
        } catch (\Throwable $th) {
            $rows = [];
            echo $th->getMessage() . "\n\r";
            $controllerLog = new Logger('SQLModule');
            $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::ERROR);
            $controllerLog->error('SQLModule', [$th->getMessage()]);
        }

        return $rows;
    }


    /**
     * Replace special character
     *
     * @return \Illuminate\Http\Response
     */
    public static function str_to_noaccent($str)
    {
        $parsed = $str;
        $parsed = preg_replace('#Ç#', 'C', $parsed);
        $parsed = preg_replace('#ç#', 'c', $parsed);
        $parsed = preg_replace('#è|é|ê|ë#', 'e', $parsed);
        $parsed = preg_replace('#È|É|Ê|Ë#', 'E', $parsed);
        $parsed = preg_replace('#à|á|â|ã|ä|å#', 'a', $parsed);
        $parsed = preg_replace('#@|À|Á|Â|Ã|Ä|Å#', 'A', $parsed);
        $parsed = preg_replace('#ì|í|î|ï#', 'i', $parsed);
        $parsed = preg_replace('#Ì|Í|Î|Ï#', 'I', $parsed);
        $parsed = preg_replace('#ð|ò|ó|ô|õ|ö#', 'o', $parsed);
        $parsed = preg_replace('#Ò|Ó|Ô|Õ|Ö#', 'O', $parsed);
        $parsed = preg_replace('#ù|ú|û|ü#', 'u', $parsed);
        $parsed = preg_replace('#Ù|Ú|Û|Ü#', 'U', $parsed);
        $parsed = preg_replace('#ý|ÿ#', 'y', $parsed);
        $parsed = preg_replace('#Ý#', 'Y', $parsed);

        return ($parsed);
    }
}
