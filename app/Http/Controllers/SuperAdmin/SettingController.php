<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\User;
use App\Models\TallyLicense;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 

class SettingController extends Controller
{
    public function index()
    {
        return view ('superadmin.settings.index');
    }

    public function saveLicense(Request $request)
    {
        $request->validate([
            'license_number' => 'required|string|max:255',
            'super_admin_user_id' => 'required|exists:users,id',
        ]);

        $license = new TallyLicense();
        $license->license_number = $request->input('license_number');
        $license->super_admin_user_id = $request->input('super_admin_user_id');
        $license->save();

        return redirect()->back()->with('success', 'License saved successfully!');
    }
}