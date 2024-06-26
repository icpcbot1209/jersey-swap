<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use React\Socket\Server;
use App\Http\Controllers\WebSocketController;

class WebSocketSecureServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocketsecure:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $loop   = Factory::create();
        $webSock = new SecureServer(
            new Server(env('CHAT_SECURE_URL'), $loop),
            $loop,
            array(
                'local_cert'        => '/var/www/html/certs/fullchain.pem', // path to your cert
                'local_pk'          => '/var/www/html/certs/privkey.pem', // path to your server private key
                'verify_peer' => FALSE
            )
        );

        // Ratchet magic
        $webServer = new IoServer(
            new HttpServer(
                new WsServer(
                    new WebSocketController()
                )
            ),
            $webSock
        );

        $loop->run();

        // $server = IoServer::factory(
        //   new HttpServer(
        //     new WsServer(
        //       new WebSocketController()
        //     )
        //   ),
        //   8080
        // );
        // $server->run();
    }
}