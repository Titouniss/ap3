<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;

class PackagesController extends Controller
{
    public $successStatus = 200;

    public function index()
    {
        return response()->json(['success' => Package::all()], $this->successStatus);
    }
}
