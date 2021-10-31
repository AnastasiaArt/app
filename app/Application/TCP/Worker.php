<?php
declare(strict_types=1);

namespace App\TCP;

use App\Events\Tcp\AfterLoopIterationEvent;
use App\Events\Tcp\BeforeLoopIterationEvent;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
use Illuminate\Support\Facades\Facade;
use Interfaces\Console\StreamHandler;
use Spiral\RoadRunner\Payload;
use Spiral\RoadRunner\Tcp\TcpWorker;
use Spiral\RoadRunnerLaravel\Application\FactoryInterface as ApplicationFactory;
use Spiral\RoadRunnerLaravel\Events;
use Spiral\RoadRunnerLaravel\WorkerInterface;
use Spiral\RoadRunnerLaravel\WorkerOptionsInterface;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Throwable;
use function Termwind\render;

class Worker implements WorkerInterface
{
    /** Laravel application factory. */
    protected ApplicationFactory $appFactory;
    /** The exception handler instance. */
    protected ExceptionHandler $exceptions;
    private OutputInterface $output;

    public function __construct()
    {
        $this->appFactory = new \Spiral\RoadRunnerLaravel\Application\Factory();
        $this->output = new StreamOutput(STDERR);
    }

    public function start(WorkerOptionsInterface $options): void
    {
        $worker = \Spiral\RoadRunner\Worker::create();

        $app = $this->createApplication($options, $worker);
        $this->exceptions = $app[ExceptionHandler::class];

        $tcpWorker = new TcpWorker($worker);

        /** @var ConfigRepository $config */
        $config = $app->make(ConfigRepository::class);

        if ($this->isDebugModeEnabled($config)) {
            $this->output->writeln('<info>TCP worker started</info>');
        }

        $this->fireEvent($app, new Events\BeforeLoopStartedEvent($app));

        while ($request = $tcpWorker->waitRequest()) {
            $startTime = microtime(true);

            if ($options->getRefreshApp()) {
                $sandbox = $this->createApplication($options, $worker);
            } else {
                $sandbox = clone $app;
            }

            /** @var Kernel $tcpKernel */
            $tcpKernel = $sandbox[Kernel::class];
            $this->setApplicationInstance($sandbox);

            if ($this->isDebugModeEnabled($config)) {
                render((string)view('console.tcp.request', [
                    'request' => $request,
                    'memory' => number_format(memory_get_usage() / 1024 / 1204, 2, '.', ''),
                ]));
            }

            try {
                $this->fireEvent($sandbox, new BeforeLoopIterationEvent($sandbox));

                $response = $tcpKernel->handle($request, $this->output);
            } catch (Throwable $e) {
                $this->exceptions->renderForConsole($this->output, $e);
                $this->exceptions->report($e);

                $response = new CloseConnection();
            } finally {
                $this->setApplicationInstance($app);

                $tcpWorker->getWorker()->respond(
                    new Payload($response->getBody(), $response->getContext())
                );

                if ($this->isDebugModeEnabled($config)) {
                    render((string)view('console.tcp.response', [
                        'request' => $request,
                        'response' => $response,
                        'memory' => number_format(memory_get_usage() / 1024 / 1204, 2, '.', ''),
                        'duration' => number_format(round(microtime(true) - $startTime, 4), 4, '.', '')
                    ]));
                }

                $this->fireEvent($sandbox, new AfterLoopIterationEvent($sandbox));

                unset($response, $request, $sandbox);
            }
        }
    }

    /**
     * Create a Laravel application instance and bind all required instances.
     */
    protected function createApplication(WorkerOptionsInterface $options, \Spiral\RoadRunner\Worker $worker): ApplicationContract
    {
        $app = $this->appFactory->create($options->getAppBasePath());
        $app->instance(\Spiral\RoadRunner\Worker::class, $worker);
        $app->instance(StreamHandler::class, new StreamHandler($this->output, $app));

        return $app;
    }

    /**
     * Set the current application in the container.
     */
    protected function setApplicationInstance(ApplicationContract $app): void
    {
        $app->instance('app', $app);
        $app->instance(Container::class, $app);

        Container::setInstance($app);

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);
    }

    protected function fireEvent(ApplicationContract $app, object $event): void
    {
        /** @var EventsDispatcher $events */
        $events = $app->make(EventsDispatcher::class);

        $events->dispatch($event);
    }

    protected function isDebugModeEnabled(ConfigRepository $config): bool
    {
        return $config->get('app.debug', false) === true;
    }
}
