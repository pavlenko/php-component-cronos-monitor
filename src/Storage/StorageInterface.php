<?php

namespace PE\Component\Cronos\Monitor\Storage;

use PE\Component\Cronos\Core\TaskInterface;

interface StorageInterface
{
    /**
     * @return int
     */
    public function getStatus(): int;

    /**
     * @param int $status
     */
    public function setStatus(int $status): void;

    /**
     * @return TaskInterface[]
     */
    public function fetchTasks(): array;

    /**
     * @param TaskInterface $task
     */
    public function insertTask(TaskInterface $task): void;

    /**
     * @param TaskInterface $task
     */
    public function updateTask(TaskInterface $task): void;

    /**
     * @param TaskInterface $task
     */
    public function removeTask(TaskInterface $task): void;
}
