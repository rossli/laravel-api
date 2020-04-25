<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Utils\Utils;
use Illuminate\Console\Command;

class TestTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:token  {mobile}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试token';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!env('APP_DEBUG')) {
            return FALSE;
        }

        $user = User::where('mobile',($this->argument('mobile')))->first();
        if ($user) {
            $token = $user->createToken('Laravel Password Grant Client')->accessToken;
            $response = ['token' => $token, 'code' => $user->getHashCode()];

            $this->info(json_encode($response,JSON_PRETTY_PRINT));
        }

        return FALSE;

    }
}
