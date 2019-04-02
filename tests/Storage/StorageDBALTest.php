<?php

namespace PE\Component\Cronos\Monitor\Tests\Storage;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
use PE\Component\Cronos\Core\Serializer;
use PE\Component\Cronos\Core\Task;
use PE\Component\Cronos\Monitor\Storage\StorageDBAL;
use PE\Component\Cronos\Core\ServerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StorageDBALTest extends TestCase
{
    const TBL_STATUSES = 'TBL_STATUSES';
    const TBL_TASKS    = 'TBL_TASKS';

    /**
     * @var StorageDBAL
     */
    private $storage;

    /**
     * @var Connection|MockObject
     */
    private $connection;

    protected function setUp()
    {
        $this->connection = $this->createMock(Connection::class);
        $this->storage    = new StorageDBAL($this->connection, self::TBL_STATUSES, self::TBL_TASKS);
    }

    public function testGetStatusDefault(): void
    {
        $this->connection
            ->expects(static::any())
            ->method('getExpressionBuilder')
            ->willReturn(new ExpressionBuilder($this->connection));

        $this->connection
            ->expects(static::once())
            ->method('createQueryBuilder')
            ->willReturn(new QueryBuilder($this->connection));

        $statement = $this->createMock(Statement::class);
        $statement->expects(static::once())->method('fetchAll')->willReturn([]);

        $this->connection
            ->expects(static::once())
            ->method('executeQuery')
            ->willReturn($statement);

        static::assertSame(ServerInterface::STATUS_INACTIVE, $this->storage->getStatus());
    }

    public function testGetStatusExists(): void
    {
        $this->connection
            ->expects(static::any())
            ->method('getExpressionBuilder')
            ->willReturn(new ExpressionBuilder($this->connection));

        $this->connection
            ->expects(static::once())
            ->method('createQueryBuilder')
            ->willReturn(new QueryBuilder($this->connection));

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects(static::once())
            ->method('fetchAll')
            ->willReturn([['value' => ServerInterface::STATUS_ACTIVE]]);

        $this->connection
            ->expects(static::once())
            ->method('executeQuery')
            ->willReturn($statement);

        static::assertSame(ServerInterface::STATUS_ACTIVE, $this->storage->getStatus());
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function testSetStatusInsert(): void
    {
        $key = ['name' => 'code'];
        $val = ['value' => 2];

        $this->connection
            ->expects(static::once())
            ->method('update')
            ->with(self::TBL_STATUSES, $val, $key)
            ->willReturn(0);

        $this->connection
            ->expects(static::once())
            ->method('insert')
            ->with(self::TBL_STATUSES, $val + $key);

        $this->storage->setStatus(2);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function testSetStatusUpdate(): void
    {
        $key = ['name' => 'code'];
        $val = ['value' => 2];

        $this->connection
            ->expects(static::once())
            ->method('update')
            ->with(self::TBL_STATUSES, $val, $key)
            ->willReturn(1);

        $this->connection
            ->expects(static::never())
            ->method('insert')
            ->with(self::TBL_STATUSES, $val + $key);

        $this->storage->setStatus(2);
    }

    public function testFetchTasks(): void
    {
        $this->connection
            ->expects(static::any())
            ->method('getExpressionBuilder')
            ->willReturn(new ExpressionBuilder($this->connection));

        $this->connection
            ->expects(static::once())
            ->method('createQueryBuilder')
            ->willReturn(new QueryBuilder($this->connection));

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects(static::once())
            ->method('fetchAll')
            ->willReturn([['task' => (new Serializer())->encode(new Task())]]);

        $this->connection
            ->expects(static::once())
            ->method('executeQuery')
            ->willReturn($statement);

        $result = $this->storage->fetchTasks();

        static::assertCount(1, $result);
        static::assertInstanceOf(Task::class, $result[0]);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function testInsertTask(): void
    {
        $task = new Task();
        $task->setID(100);

        $data = [
            'id'        => 100,
            'task'      => (new Serializer())->encode($task),
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedAt' => date('Y-m-d H:i:s'),
        ];

        $this->connection
            ->expects(static::once())
            ->method('insert')
            ->with(self::TBL_TASKS, $data);

        $this->storage->insertTask($task);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function testUpdateTask(): void
    {
        $task = new Task();
        $task->setID(100);

        $data = [
            'task'      => (new Serializer())->encode($task),
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        $this->connection
            ->expects(static::once())
            ->method('update')
            ->with(self::TBL_TASKS, $data, ['id' => 100]);

        $this->storage->updateTask($task);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function testRemoveTask(): void
    {
        $task = new Task();
        $task->setID(100);

        $this->connection->expects(static::once())->method('delete')->with(self::TBL_TASKS, ['id' => 100]);

        $this->storage->removeTask($task);
    }
}
