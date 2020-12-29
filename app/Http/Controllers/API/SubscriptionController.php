<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionComponent;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public $successStatus = 200;

    public function components()
    {
        return response()->json(['success' => SubscriptionComponent::all()], $this->successStatus);
    }
}
