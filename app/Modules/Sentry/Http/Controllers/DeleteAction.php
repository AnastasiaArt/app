<?php
declare(strict_types=1);

namespace Modules\Sentry\Http\Controllers;

use App\Contracts\EventsRepository;
use Interfaces\Http\Controllers\Controller;
use Ramsey\Uuid\UuidInterface;

class DeleteAction extends Controller
{
    public function __invoke(EventsRepository $events, UuidInterface $uuid)
    {
        $event = $events->findByPK($uuid);
        if (!$event) {
            abort(404);
        }

        $events->delete($uuid);

        return redirect()->route('sentry', status: 303);
    }
}
