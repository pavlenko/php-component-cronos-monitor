<?php

namespace PE\Component\Cronos\Monitor\Tests;

use PE\Component\Cronos\Core\ClientInterface;
use PE\Component\Cronos\Core\TaskInterface;
use PE\Component\Cronos\Monitor\MonitorAPI;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MonitorAPITest extends TestCase
{
    /**
     * @var ClientInterface|MockObject
     */
    private $transport;

    /**
     * @var MonitorAPI
     */
    private $client;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(ClientInterface::class);
        $this->client    = new MonitorAPI($this->transport);
    }

    public function testGetTasks(): void
    {
        $this->transport
            ->expects(static::once())
            ->method('request')
            ->with(MonitorAPI::GET_STATUS)
            ->willReturn(1);

        static::assertSame(1, $this->client->getStatus());
    }

    public function testGetStatus(): void
    {
        /* @var $task TaskInterface */
        $task = $this->createMock(TaskInterface::class);

        $this->transport
            ->expects(static::once())
            ->method('request')
            ->with(MonitorAPI::GET_TASKS)
            ->willReturn([$task]);

        static::assertEquals([$task], $this->client->getTasks());
    }
}
