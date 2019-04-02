<?php

namespace PE\Component\Cronos\Monitor\Storage;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use PE\Component\Cronos\Core\Serializer;
use PE\Component\Cronos\Core\SerializerInterface;
use PE\Component\Cronos\Core\TaskInterface;
use PE\Component\Cronos\Core\ServerInterface;

final class StorageDBAL implements StorageInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $tableStatuses;

    /**
     * @var string
     */
    private $tableTasks;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param Connection $connection
     * @param string     $tableStatuses
     * @param string     $tableTasks
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        Connection $connection,
        string $tableStatuses = 'monitor_statuses',
        string $tableTasks = 'monitor_tasks',
        SerializerInterface $serializer = null
    ) {
        $this->connection    = $connection;
        $this->tableStatuses = $tableStatuses;
        $this->tableTasks    = $tableTasks;
        $this->serializer    = $serializer ?: new Serializer();
    }

    /**
     * @throws DBALException
     * @codeCoverageIgnore
     */
    public function initialize(): void
    {
        $platform  = $this->connection->getDatabasePlatform();
        $schemaOld = $this->connection->getSchemaManager()->createSchema();
        $schemaNew = clone $schemaOld;

        if ($schemaNew->hasTable($this->tableStatuses)) {
            $schemaNew->dropTable($this->tableStatuses);
        }

        if ($schemaNew->hasTable($this->tableTasks)) {
            $schemaNew->dropTable($this->tableTasks);
        }

        $table = $schemaNew->createTable($this->tableStatuses);
        $table->addColumn('name', Type::STRING, ['length' => 255]);
        $table->addColumn('value', Type::STRING, ['length' => 255]);
        $table->setPrimaryKey(['name']);

        $table = $schemaNew->createTable($this->tableTasks);
        $table->addColumn('id', Type::STRING, ['length' => 255]);
        $table->addColumn('moduleID', Type::STRING, ['length' => 255]);
        $table->addColumn('createdAt', Type::DATETIME);
        $table->addColumn('updatedAt', Type::DATETIME);
        $table->setPrimaryKey(['id', 'moduleID']);

        foreach ($schemaOld->getMigrateToSql($schemaNew, $platform) as $sql) {
            $this->connection->executeQuery($sql);
        }
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->from($this->tableStatuses)
            ->where($query->expr()->eq('name', 'code'));

        $result = $query->execute()->fetchAll();

        return (int) ($result[0]['value'] ?? ServerInterface::STATUS_INACTIVE);
    }

    /**
     * @inheritDoc
     * @throws DBALException
     */
    public function setStatus(int $status): void
    {
        if (!$this->connection->update($this->tableStatuses, ['value' => $status], ['name' => 'code'])) {
            $this->connection->insert($this->tableStatuses, ['value' => $status, 'name' => 'code']);
        }
    }

    /**
     * @inheritDoc
     */
    public function fetchTasks(): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->from($this->tableTasks)
            ->orderBy('updatedAt', 'DESC');

        $data = [];
        $rows = $query->execute()->fetchAll();

        foreach ($rows as $row) {
            $data[] = $this->serializer->decode($row['task'] ?? null);
        }

        return array_filter($data);
    }

    /**
     * @inheritDoc
     * @throws DBALException
     */
    public function insertTask(TaskInterface $task): void
    {
        $data = [
            'id'        => $task->getID(),
            'task'      => $this->serializer->encode($task),
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedAt' => date('Y-m-d H:i:s'),
        ];

        $this->connection->insert($this->tableTasks, $data);
    }

    /**
     * @inheritDoc
     * @throws DBALException
     */
    public function updateTask(TaskInterface $task): void
    {
        $data = [
            'task'      => $this->serializer->encode($task),
            'updatedAt' => date('Y-m-d H:i:s'),
        ];

        $this->connection->update($this->tableTasks, $data, ['id' => $task->getID()]);
    }

    /**
     * @inheritDoc
     * @throws DBALException
     */
    public function removeTask(TaskInterface $task): void
    {
        $this->connection->delete($this->tableTasks, ['id' => $task->getID()]);
    }
}
