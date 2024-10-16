<?php

namespace App\Facades;

use App\Mail\Superadmin\ApproveMail;
use App\Models\NotificationsSetting;
// use App\Models\Order;
use App\Models\Plan;
use App\Models\RequestDomain;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserCoupon;
use App\Notifications\Superadmin\ApproveNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\MailTemplates\Models\MailTemplate;
use Stancl\Tenancy\Database\Models\Domain;

class Utility
{
    public function settings()
    {
        $data = DB::table('settings');
        $data = $data->get();
        $settings = [
            'date_format' => 'M j, Y',
            'time_format' => 'g:i A',
        ];
        foreach ($data as $row) {
            $settings[$row->key] = $row->value;
        }
        return $settings;
    }

    // public function date_format($date)
    // {
    //     return Carbon::parse($date)->format(Self::getsettings('date_format'));
    // }

    // public function time_format($date)
    // {
    //     return Carbon::parse($date)->format(Self::getsettings('time_format'));
    // }

    public function date_time_format($date)
    {
        return Carbon::parse($request->created_at)->format('Y-m-d H:i:s');
    }

}
