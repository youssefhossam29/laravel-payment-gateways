<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // Show Payment Form
    public function create()
    {
        return view('payments.form');
    }

    // Redirect to success page
    public function success()
    {
        return view('payments.success');
    }

    // Redirect to failed page
    public function failed()
    {
        return view('payments.fail');
    }
}
