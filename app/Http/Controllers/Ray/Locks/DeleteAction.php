<?php
declare(strict_types=1);

namespace App\Http\Controllers\Ray\Locks;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;

class DeleteAction extends Controller
{
    public function __invoke(Request $request, Repository $cache, string $hash)
    {
        $cache->put($hash, [
            'active' => false,
            'stop_execution' => (bool)$request->stop_execution,
        ]);
    }
}
