<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function sendOTP(Request $request)
    {
        $request->validate([
            'phone' => 'required|numeric|digits:10',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return back()->withErrors(['phone' => 'Phone number not registered.']);
        }

        // Generate OTP
        // $otp = rand(1000, 9999);
        $otp = 1234;
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        // Send OTP
        // $this->sendOTPSMS($request->phone, $otp);

        return view('auth.verify-otp', ['phone' => $request->phone]);
    }

    public function verifyOTP(Request $request)
    {
        $request->validate([
            'phone' => 'required|numeric|digits:10',
            'otp' => 'required|numeric|digits:4'
        ]);

        $user = User::where('phone', $request->phone)->first();

        if ($user && $user->otp == $request->otp && now()->lessThanOrEqualTo($user->otp_expires_at)) {
            $user->is_phone_verified = true;
            $user->otp = null; // Clear OTP after successful verification
            $user->otp_expires_at = null;
            $user->save();

            auth()->login($user);

            return redirect()->route('dashboard');
        } else {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.']);
        }
    }

    private function sendOTPSMS($phone, $otp)
    {
        $message = "{$otp} is your OTP for TallyConnects Software Activation. OTP is valid for 10 minutes. Regards TallyConnects";

        $response = Http::asForm()->post('https://www.smsgateway.center/SMSApi/rest/send', [
            'userId' => 'irriion',
            'password' => 'Excel@123#',
            'mobile' => '91' . $phone,
            'msg' => $message,
            'senderId' => 'XLTALY',
            'msgType' => 'text',
            'duplicateCheck' => 'true',
            'format' => 'json',
            'sendMethod' => 'simpleMsg',
        ]);

        return $response->json();
    }
}
