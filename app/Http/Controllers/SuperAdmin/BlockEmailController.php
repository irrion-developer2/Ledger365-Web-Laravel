<?php

namespace App\Http\Controllers\SuperAdmin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use App\Models\BlockEmail;

class BlockEmailController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = BlockEmail::select(['id', 'email', 'remark', 'created_at']);
            return DataTables::of($data)
                ->editColumn('created_at', function ($row) {
                    return $row->created_at ? $row->created_at->format('d-m-Y') : '';
                })
                ->make(true);
        }

        return view('blockemails.add_blockemail');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:block_emails,email',
        ]);

        
        BlockEmail::create([
            'email' => $request->email,
            'remark' => $request->remark,
        ]);

        return redirect()->back()->with('success', 'Email has been successfully blocked.');
    }
}
