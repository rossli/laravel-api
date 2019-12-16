<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CancelOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order_id)
    {
        $this->order_id = $order_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        $order = Order::where('status', Order::STATUS_WAIT_PAY)->where('id', $this->order_id)->first();
        if ($order) {
            $user=User::find('user_id');
            if($user){
                $user->currency+=$order->coupon_deduction/100;
                $user->save();
            }

            $order->status = Order::STATUS_CANCEL;
            $order->cancel_type = Order::CANCEL_TYPE_AUTO;
            $order->cancel_time = now();
            $order->save();
        }
    }
}
