<?php declare(strict_types=1);

namespace Od\Scheduler\Model\Job;

class HandlerPool
{
    /**
     * @var JobHandlerInterface[]
     */
    private array $handlers = [];
    private iterable $rawHandlers;

    public function __construct(iterable $handlers)
    {
        $this->rawHandlers = $handlers;
    }

    public function get(string $code): JobHandlerInterface
    {
        $this->initHandlers();

        return $this->handlers[$code] ?? new Handler\Dummy();
    }

    public function all(): array
    {
        $this->initHandlers();

        return $this->handlers;
    }

    private function initHandlers()
    {
        if (empty($this->handlers) && $this->rawHandlers instanceof \Traversable) {
            $this->handlers = iterator_to_array($this->rawHandlers);
        }
    }
}
