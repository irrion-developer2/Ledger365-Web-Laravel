<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register'); 
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'numeric', 'digits:10', 'unique:users'],
            'tally_connector_id' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $registrationData = $request->only(['name', 'email', 'phone', 'tally_connector_id']);

        $request->session()->put('registration_data', $registrationData);

        $otp = rand(1000, 9999);
        $request->session()->put('otp', $otp);
        $request->session()->put('otp_created_at', now());

        $this->sendOTPSMS($request->phone, $otp);

        return redirect()->route('verify-register-otp')->with('phone', $request->phone)->with('success', 'OTP has been send successfully.');
    }

    public function showVerifyOtpForm()
    {
        if (!session()->has('registration_data') || !session()->has('otp')) {
            return redirect()->route('register')->withErrors(['error' => 'Session expired. Please register again.']);
        }

        return view('auth.verify-register-otp');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric|digits:4',
        ]);

        $sessionOtp = $request->session()->get('otp');
        $otpCreatedAt = $request->session()->get('otp_created_at');

        if (now()->diffInMinutes($otpCreatedAt) > 10) {
            return redirect()->route('verify-otp')->withErrors(['otp' => 'OTP has expired. Please resend OTP.']);
        }
        if ($sessionOtp != $request->otp) {
            return redirect()->back()->withErrors(['otp' => 'Invalid OTP.']);
        }

        $data = $request->session()->get('registration_data');

        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'role' => $data['role'] ?? 'Owner',
                'tally_connector_id' => $data['tally_connector_id'],
                'password' => Hash::make(Str::random(12)),
                'remember_token' => Str::random(60),
                'is_phone_verified' => true, 
            ]);
        } catch (\Exception $e) {
            return redirect()->route('register')->withErrors(['error' => 'Failed to create user. Please try again.']);
        }

        $request->session()->forget(['registration_data', 'otp', 'otp_created_at']);

        auth()->login($user);

        return redirect()->route('dashboard')->with('success', 'Registration successful.');
    }

    public function resendOtp(Request $request)
    {
        if (!$request->session()->has('registration_data')) {
            return redirect()->route('register')->withErrors(['error' => 'Session expired. Please register again.']);
        }

        $otp = rand(1000, 9999);
        $request->session()->put('otp', $otp);
        $request->session()->put('otp_created_at', now());

        $data = $request->session()->get('registration_data');
        $phone = $data['phone'];

        $this->sendOTPSMS($phone, $otp);

        return redirect()->route('verify-register-otp')->with('phone', $phone)->with('success', 'OTP has been resent.');
    }

    private function sendOTPSMS($phone, $otp)
    {
        $message = "{$otp} is your OTP for TallyConnects Software Activation. OTP is valid for 10 minutes. Regards, TallyConnects";

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
