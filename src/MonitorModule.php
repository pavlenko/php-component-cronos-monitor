<?php

namespace PE\Component\Cronos\Monitor;

use PE\Component\Cronos\Core\ClientAction;
use PE\Component\Cronos\Core\Module;
use PE\Component\Cronos\Core\QueueInterface;
use PE\Component\Cronos\Core\ServerInterface;
use PE\Component\Cronos\Core\TaskInterface;
use PE\Component\Cronos\Monitor\Storage\StorageInterface;

class MonitorModule extends Module
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @param StorageInterface $storage
     */
    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @inheritDoc
     */
    public function attachServer(ServerInterface $server): void
    {
        $server->attachListener(ServerInterface::EVENT_STARTING, [$this, 'onStarting']);
        $server->attachListener(ServerInterface::EVENT_STARTED, [$this, 'onStarted']);
        $server->attachListener(ServerInterface::EVENT_ENQUEUE_TASKS, [$this, 'onEnqueueTasks']);
        $server->attachListener(ServerInterface::EVENT_SET_TASK_EXECUTED, [$this, 'onTaskExecuted']);
        $server->attachListener(ServerInterface::EVENT_SET_TASK_ESTIMATE, [$this, 'onTaskEstimate']);
        $server->attachListener(ServerInterface::EVENT_SET_TASK_PROGRESS, [$this, 'onTaskProgress']);
        $server->attachListener(ServerInterface::EVENT_SET_TASK_FINISHED, [$this, 'onTaskFinished']);
        $server->attachListener(ServerInterface::EVENT_STOPPING, [$this, 'onStopping']);
        $server->attachListener(ServerInterface::EVENT_STOPPED, [$this, 'onStopped']);
        $server->attachListener(ServerInterface::EVENT_CLIENT_ACTION, [$this, 'onClientAction']);
    }

    /**
     * @inheritDoc
     */
    public function detachServer(ServerInterface $server): void
    {
        $server->detachListener(ServerInterface::EVENT_STARTING, [$this, 'onStarting']);
        $server->detachListener(ServerInterface::EVENT_STARTED, [$this, 'onStarted']);
        $server->detachListener(ServerInterface::EVENT_ENQUEUE_TASKS, [$this, 'onEnqueueTasks']);
        $server->detachListener(ServerInterface::EVENT_SET_TASK_EXECUTED, [$this, 'onTaskExecuted']);
        $server->detachListener(ServerInterface::EVENT_SET_TASK_ESTIMATE, [$this, 'onTaskEstimate']);
        $server->detachListener(ServerInterface::EVENT_SET_TASK_PROGRESS, [$this, 'onTaskProgress']);
        $server->detachListener(ServerInterface::EVENT_SET_TASK_FINISHED, [$this, 'onTaskFinished']);
        $server->detachListener(ServerInterface::EVENT_STOPPING, [$this, 'onStopping']);
        $server->detachListener(ServerInterface::EVENT_STOPPED, [$this, 'onStopped']);
        $server->detachListener(ServerInterface::EVENT_CLIENT_ACTION, [$this, 'onClientAction']);
    }

    /**
     * @internal
     */
    public function onStarting(): void
    {
        $this->storage->setStatus(ServerInterface::STATUS_STARTING);
    }

    /**
     * @internal
     */
    public function onStarted(): void
    {
        $this->storage->setStatus(ServerInterface::STATUS_ACTIVE);
    }

    /**
     * @internal
     *
     * @param QueueInterface $queue
     */
    public function onEnqueueTasks(QueueInterface $queue): void
    {
        foreach ($queue->contents() as $task) {
            $this->storage->insertTask($task);
        }
    }

    /**
     * @internal
     *
     * @param TaskInterface $task
     */
    public function onTaskExecuted(TaskInterface $task): void
    {
        $this->storage->updateTask($task);
    }

    /**
     * @internal
     *
     * @param TaskInterface $task
     */
    public function onTaskEstimate(TaskInterface $task): void
    {
        $this->storage->updateTask($task);
    }

    /**
     * @internal
     *
     * @param TaskInterface $task
     */
    public function onTaskProgress(TaskInterface $task): void
    {
        $this->storage->updateTask($task);
    }

    /**
     * @internal
     *
     * @param TaskInterface $task
     */
    public function onTaskFinished(TaskInterface $task): void
    {
        $this->storage->removeTask($task);
    }

    /**
     * @internal
     */
    public function onStopping(): void
    {
        $this->storage->setStatus(ServerInterface::STATUS_STOPPING);
    }

    /**
     * @internal
     */
    public function onStopped(): void
    {
        $this->storage->setStatus(ServerInterface::STATUS_INACTIVE);
    }

    /**
     * @internal
     *
     * @param ClientAction $clientAction
     */
    public function onClientAction(ClientAction $clientAction): void
    {
        switch ($clientAction->getName()) {
            case MonitorAPI::GET_STATUS:
                $clientAction->setResult($this->storage->getStatus());
                break;
            case MonitorAPI::GET_TASKS:
                $clientAction->setResult($this->storage->fetchTasks());
                break;
        }
    }
}
