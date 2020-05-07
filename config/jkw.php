<?php

return [
    'logo' => 'https://jkwedu-new.oss-cn-beijing.aliyuncs.com/Logo-white.png',
    'cdn_domain' => env('CDN_DOMAIN','//jkwedu-new.oss-cn-beijing.aliyuncs.com'),
    'default_avatar' => env('DEFAULT_AVATAR','default_avatar.jpeg'),
    'index_url'=>env('INDEX_URL','https://www.jkwedu.net'),
    'u_index_url'=>env('U_INDEX_URL','https://u.jkwedu.net'),
    'cancel_time'=>env('CANCEL_TIME',60),
    'suggest'=>env('SUGGEST','o_ysnwFSWlmpHXi1tWeMs8hH_4T'),
    'withdraw_amount'=>env('WITHDRAW_AMOUNT',10),  //最少提现金额
    'withdraw_amount_daily_limit'=>env('WITHDRAW_AMOUNT_DAILY_LIMIT',1000),  //每日提现金额
];
