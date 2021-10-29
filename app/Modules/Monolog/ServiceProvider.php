<?php
declare(strict_types=1);

namespace Modules\Monolog;

use App\TCP\Kernel;
use Modules\Monolog\Console\TcpHandler;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot(): void
    {
        $this->app[Kernel::class]->addHandler('monolog', TcpHandler::class);
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'monolog');
    }
}
