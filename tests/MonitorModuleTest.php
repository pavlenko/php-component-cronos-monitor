<?php

namespace PE\Component\Cronos\Monitor\Tests;

use PE\Component\Cronos\Core\ClientAction;
use PE\Component\Cronos\Core\TaskInterface;
use PE\Component\Cronos\Monitor\MonitorAPI;
use PE\Component\Cronos\Monitor\MonitorModule;
use PE\Component\Cronos\Monitor\Storage\StorageInterface;
use PE\Component\Cronos\Core\QueueInterface;
use PE\Component\Cronos\Core\ServerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MonitorModuleTest extends TestCase
{
    /**
     * @var StorageInterface|MockObject
     */
    private $storage;

    /**
     * @var MonitorModule
     */
    private $module;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(StorageInterface::class);
        $this->module  = new MonitorModule($this->storage);
    }

    public function testAttachServer(): void
    {
        /* @var $server ServerInterface|MockObject */
        $server = $this->createMock(ServerInterface::class);
        $server->expects(static::exactly(10))->method('attachListener')->withConsecutive(
            [ServerInterface::EVENT_STARTING, [$this->module, 'onStarting']],
            [ServerInterface::EVENT_STARTED, [$this->module, 'onStarted']],
            [ServerInterface::EVENT_ENQUEUE_TASKS, [$this->module, 'onEnqueueTasks']],
            [ServerInterface::EVENT_SET_TASK_EXECUTED, [$this->module, 'onTaskExecuted']],
            [ServerInterface::EVENT_SET_TASK_ESTIMATE, [$this->module, 'onTaskEstimate']],
            [ServerInterface::EVENT_SET_TASK_PROGRESS, [$this->module, 'onTaskProgress']],
            [ServerInterface::EVENT_SET_TASK_FINISHED, [$this->module, 'onTaskFinished']],
            [ServerInterface::EVENT_STOPPING, [$this->module, 'onStopping']],
            [ServerInterface::EVENT_STOPPED, [$this->module, 'onStopped']],
            [ServerInterface::EVENT_CLIENT_ACTION, [$this->module, 'onClientAction']]
        );

        $this->module->attachServer($server);
    }

    public function testDetachServer(): void
    {
        /* @var $server ServerInterface|MockObject */
        $server = $this->createMock(ServerInterface::class);
        $server->expects(static::exactly(10))->method('detachListener')->withConsecutive(
            [ServerInterface::EVENT_STARTING, [$this->module, 'onStarting']],
            [ServerInterface::EVENT_STARTED, [$this->module, 'onStarted']],
            [ServerInterface::EVENT_ENQUEUE_TASKS, [$this->module, 'onEnqueueTasks']],
            [ServerInterface::EVENT_SET_TASK_EXECUTED, [$this->module, 'onTaskExecuted']],
            [ServerInterface::EVENT_SET_TASK_ESTIMATE, [$this->module, 'onTaskEstimate']],
            [ServerInterface::EVENT_SET_TASK_PROGRESS, [$this->module, 'onTaskProgress']],
            [ServerInterface::EVENT_SET_TASK_FINISHED, [$this->module, 'onTaskFinished']],
            [ServerInterface::EVENT_STOPPING, [$this->module, 'onStopping']],
            [ServerInterface::EVENT_STOPPED, [$this->module, 'onStopped']],
            [ServerInterface::EVENT_CLIENT_ACTION, [$this->module, 'onClientAction']]
        );

        $this->module->detachServer($server);
    }

    public function testOnStarting(): void
    {
        $this->storage->expects(static::once())->method('setStatus')->with(ServerInterface::STATUS_STARTING);
        $this->module->onStarting();
    }

    public function testOnStarted(): void
    {
        $this->storage->expects(static::once())->method('setStatus')->with(ServerInterface::STATUS_ACTIVE);
        $this->module->onStarted();
    }

    public function testOnEnqueueTasks(): void
    {
        /* @var $task1 TaskInterface|MockObject */
        $task1 = $this->createMock(TaskInterface::class);

        /* @var $task2 TaskInterface|MockObject */
        $task2 = $this->createMock(TaskInterface::class);

        /* @var $queue QueueInterface|MockObject */
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects(static::once())->method('contents')->willReturn([$task1, $task2]);

        $this->storage->expects(static::exactly(2))->method('insertTask')->withConsecutive([$task1], [$task2]);

        $this->module->onEnqueueTasks($queue);
    }

    public function testOnTaskExecuted(): void
    {
        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);

        $this->storage->expects(static::once())->method('updateTask')->with($task);
        $this->module->onTaskExecuted($task);
    }

    public function testOnTaskEstimate(): void
    {
        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);

        $this->storage->expects(static::once())->method('updateTask')->with($task);
        $this->module->onTaskEstimate($task);
    }

    public function testOnTaskProgress(): void
    {
        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);

        $this->storage->expects(static::once())->method('updateTask')->with($task);
        $this->module->onTaskProgress($task);
    }

    public function testOnTaskFinished(): void
    {
        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);

        $this->storage->expects(static::once())->method('removeTask')->with($task);
        $this->module->onTaskFinished($task);
    }

    public function testOnStopping(): void
    {
        $this->storage->expects(static::once())->method('setStatus')->with(ServerInterface::STATUS_STOPPING);
        $this->module->onStopping();
    }

    public function testOnStopped(): void
    {
        $this->storage->expects(static::once())->method('setStatus')->with(ServerInterface::STATUS_INACTIVE);
        $this->module->onStopped();
    }

    public function testOnClientRequestStatus(): void
    {
        $status = ServerInterface::STATUS_INACTIVE;

        $this->storage->expects(static::once())->method('getStatus')->willReturn($status);

        $this->module->onClientAction($event = new ClientAction(MonitorAPI::GET_STATUS, null));

        self::assertSame($status, $event->getResult());
    }

    public function testOnClientRequestTasks(): void
    {
        /* @var $task1 TaskInterface|MockObject */
        $task1 = $this->createMock(TaskInterface::class);

        /* @var $task2 TaskInterface|MockObject */
        $task2 = $this->createMock(TaskInterface::class);

        $this->storage->expects(static::once())->method('fetchTasks')->willReturn([$task1, $task2]);

        $this->module->onClientAction($event = new ClientAction(MonitorAPI::GET_TASKS, null));

        self::assertSame([$task1, $task2], $event->getResult());
    }
}
