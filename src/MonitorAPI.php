<?php

namespace PE\Component\Cronos\Monitor;

use PE\Component\Cronos\Core\ClientInterface;
use PE\Component\Cronos\Core\TaskInterface;

class MonitorAPI
{
    public const GET_STATUS = 'monitor:get_status';
    public const GET_TASKS  = 'monitor:get_tasks';

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return (int) $this->client->request(self::GET_STATUS, null);
    }

    /**
     * @return TaskInterface[]
     */
    public function getTasks(): array
    {
        $tasks = $this->client->request(self::GET_TASKS, null);

        return array_filter($tasks, function ($task) {
            return $task instanceof TaskInterface;
        });
    }
}
