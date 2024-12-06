<?php

namespace App\Http\Controllers\SuperAdmin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\BlockEmail;

class BlockEmailController extends Controller
{
    public function index(Request $request)
    {
        return view('blockemails.add_blockemail');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:block_emails,email',
        ]);

        
        BlockEmail::create([
            'email' => $request->email,
        ]);

        return redirect()->back()->with('success', 'Email has been successfully blocked.');
    }
}
