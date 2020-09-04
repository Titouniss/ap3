<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DataType;
use Illuminate\Http\Request;

class DataTypeController extends Controller
{
    public $successStatus = 200;

    /** 
     * list of items api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function index()
    {
        return response()->json(['success' => DataType::orderBy('display_name_plurial')->with('dataRows')->get()], $this->successStatus);
    }
}
