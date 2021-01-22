<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;

class ModuleDataRow extends BaseModel
{
    protected $fillable = ['source', 'default_value', 'details', 'data_row_id', 'module_data_type_id'];
    protected $hidden = ['created_at', 'updated_at'];

    public function moduleDataType()
    {
        return $this->belongsTo(ModuleDataType::class, 'module_data_type_id', 'id');
    }

    public function dataRow()
    {
        return $this->belongsTo(DataRow::class, 'data_row_id', 'id');
    }

    public function applyDetailsToValue($value, &$lowestUniqueId = 0)
    {
        $newValue = $value;
        if ($newValue) {
            $table = app($this->moduleDataType->dataType->model);
            $details = json_decode($this->details);
            $drDetails = json_decode($this->dataRow->details);

            switch ($this->dataRow->type) {
                case 'integer':
                    $newValue = intval(ceil($newValue));
                    break;
                case 'datetime':
                    if ($details && isset($details->format)) {
                        $newValue = Carbon::createFromFormat($details->format, $newValue);
                    } else {
                        $newValue = new Carbon($newValue);
                    }
                    break;
                case 'enum':
                    if ($details && isset($details->options)) {
                        $newValue = $details->options->{$newValue} ?? $this->default_value;
                    }
                    break;
                case 'relationship':
                    if ($value && $drDetails && isset($drDetails->model)) {
                        $newValue = ModelHasOldId::firstOrNew([
                            'company_id' => $this->moduleDataType->module->company_id,
                            'model' => $drDetails->model,
                            'old_id' => $value,
                        ])->new_id;

                        if (!$newValue && $details && isset($details->pivot_type) && isset($details->pivot_to_model) && isset($details->pivot_local_id) && isset($details->pivot_foreign_id)) {
                            switch ($details->pivot_type) {
                                case 'hasOneThrough':
                                    // Trouver l'entité de déstination
                                    $destinationId = ModelHasOldId::firstOrNew([
                                        'company_id' => $this->moduleDataType->module->company_id,
                                        'model' => $details->pivot_to_model,
                                        'old_id' => $value,
                                    ])->new_id;

                                    // S'il existe on peut créer le pont entre les deux
                                    if ($destinationId) {
                                        $pivot = app($drDetails->model)->firstOrNew([$details->pivot_foreign_id => $destinationId]);
                                        if (in_array($drDetails->model, ['App\\Models\\TasksBundle']) && !$pivot->company_id) {
                                            $pivot->company_id = $this->moduleDataType->module->company_id;
                                        }
                                        $pivot->save();
                                        $newValue = $pivot->{$details->pivot_local_id};
                                        ModelHasOldId::create([
                                            'company_id' => $this->moduleDataType->module->company_id,
                                            'model' => $drDetails->model,
                                            'old_id' => $value,
                                            'new_id' => $newValue
                                        ]);
                                    }
                                    break;

                                default:
                                    break;
                            }
                        }

                        if (isset($drDetails->pivot_type) && $newValue) {
                            switch ($drDetails->pivot_type) {
                                case 'hasOneThrough':
                                    $values = [$drDetails->pivot_foreign_id => $newValue];
                                    if (property_exists($drDetails->pivot_model, 'company_id')) {
                                        $values['company_id'] = $this->moduleDataType->module->company_id;
                                    }
                                    $newValue = app($drDetails->pivot_model)->firstOrCreate($values)->{$drDetails->pivot_local_id};
                                    break;

                                default:
                                    break;
                            }
                        }
                    }

                    if ($newValue == null && $this->dataRow->required) {
                        throw new Exception($this->moduleDataType->dataType->model . " : " . $this->dataRow->field . " cannot be null");
                    }
                    break;
                case 'string':
                    if ($details) {
                        if (isset($details->split)) {
                            $valueArray = explode($details->split->delimiter, $newValue);
                            if ($details->split->keep == 'start') {
                                $newValue = implode($details->split->delimiter, array_slice($valueArray, 0, ceil(count($valueArray) / 2)));
                            } else {
                                $newValue = implode($details->split->delimiter, array_slice($valueArray, floor(count($valueArray) / 2)));
                            }
                        }

                        if (isset($details->format)) {
                            $formattedValue = "";
                            if (isset($details->format->prefix)) {
                                switch ($details->format->prefix) {
                                    case 'company':
                                        $formattedValue .= strtolower($this->moduleDataType->module->company->name) . $details->format->glue;
                                        break;

                                    default:
                                        break;
                                }
                            }

                            $valueArray = explode($details->format->delimiter, $newValue);
                            if (isset($details->format->reverse)) {
                                if ($details->format->reverse) {
                                    $valueArray = array_reverse($valueArray);
                                }
                            }
                            if (isset($details->format->case)) {
                                switch ($details->format->case) {
                                    case 'lowerCamelCase':
                                        $formattedValue .= implode("", array_map(function ($v, $k) {
                                            return $k ? ucfirst(strtolower($v)) : strtolower($v);
                                        }, $valueArray, array_keys($valueArray)));
                                        break;
                                    case 'upperCamelCase':
                                        $formattedValue .= implode("", array_map(function ($v) {
                                            return ucfirst(strtolower($v));
                                        }, $valueArray));
                                        break;

                                    default:
                                        break;
                                }
                            }

                            if (isset($details->format->suffix)) {
                                switch ($details->format->suffix) {
                                    case 'company':
                                        $formattedValue .= $details->format->glue . strtolower($this->moduleDataType->module->company->name);
                                        break;

                                    default:
                                        break;
                                }
                            }

                            $newValue = $formattedValue;
                        }
                    }

                    if ($drDetails) {
                        if (isset($drDetails->max_length)) {
                            if ($drDetails->max_length) {
                                $newValue = substr($newValue, 0, intval($drDetails->max_length));
                            }
                        }
                        if (isset($drDetails->is_password)) {
                            if ($drDetails->is_password) {
                                $newValue = bcrypt($newValue);
                            }
                        }
                        if (isset($drDetails->remove_special_chars)) {
                            if ($drDetails->remove_special_chars) {
                                $newValue = $this->str_to_noaccent($newValue);
                            }
                        }
                        if (isset($drDetails->is_unique)) {
                            if ($drDetails->is_unique) {
                                $uniqueValue = $newValue;
                                while ($table->where($this->dataRow->field, $uniqueValue)->exists()) {
                                    $uniqueValue = $newValue . ++$lowestUniqueId;
                                }
                                $newValue = $uniqueValue;
                            }
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        return $newValue;
    }

    /**
     * Replace special characters
     */
    public function str_to_noaccent($str)
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

        return $parsed;
    }
}
