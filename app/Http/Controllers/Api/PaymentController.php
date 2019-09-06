<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\PayLog;
use App\Models\SmsRecord;
use App\Models\User;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PaymentController extends BaseController
{
    private $app;

    public function __construct()
    {
        $config = [
            // 必要配置
            'app_id' => config('wechat.payment.default.app_id'),
            'mch_id' => config('wechat.payment.default.mch_id'),
            'key' => config('wechat.payment.default.key'),   // API 密钥
        ];

        $this->app = Factory::payment($config);
    }


}
