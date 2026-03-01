<?php

declare(strict_types=1);

namespace Waaseyaa\Database\Tests\Unit;

use Waaseyaa\Database\DeleteInterface;
use Waaseyaa\Database\InsertInterface;
use Waaseyaa\Database\PdoDatabase;
use Waaseyaa\Database\SchemaInterface;
use Waaseyaa\Database\SelectInterface;
use Waaseyaa\Database\TransactionInterface;
use Waaseyaa\Database\UpdateInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdoDatabase::class)]
final class PdoDatabaseTest extends TestCase
{
    private PdoDatabase $db;

    protected function setUp(): void
    {
        $this->db = PdoDatabase::createSqlite();
    }

    public function testCreateSqliteReturnsInstance(): void
    {
        $db = PdoDatabase::createSqlite();

        $this->assertInstanceOf(PdoDatabase::class, $db);
    }

    public function testConstructorSetsErrorModeException(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $db = new PdoDatabase($pdo);

        $this->assertSame(
            \PDO::ERRMODE_EXCEPTION,
            $db->getPdo()->getAttribute(\PDO::ATTR_ERRMODE)
        );
    }

    public function testSelectReturnsSelectInterface(): void
    {
        $select = $this->db->select('users', 'u');

        $this->assertInstanceOf(SelectInterface::class, $select);
    }

    public function testInsertReturnsInsertInterface(): void
    {
        $insert = $this->db->insert('users');

        $this->assertInstanceOf(InsertInterface::class, $insert);
    }

    public function testUpdateReturnsUpdateInterface(): void
    {
        $update = $this->db->update('users');

        $this->assertInstanceOf(UpdateInterface::class, $update);
    }

    public function testDeleteReturnsDeleteInterface(): void
    {
        $delete = $this->db->delete('users');

        $this->assertInstanceOf(DeleteInterface::class, $delete);
    }

    public function testSchemaReturnsSchemaInterface(): void
    {
        $schema = $this->db->schema();

        $this->assertInstanceOf(SchemaInterface::class, $schema);
    }

    public function testTransactionReturnsTransactionInterface(): void
    {
        $transaction = $this->db->transaction();

        $this->assertInstanceOf(TransactionInterface::class, $transaction);
        // Clean up: commit the transaction so SQLite doesn't complain.
        $transaction->commit();
    }

    public function testQueryExecutesRawSql(): void
    {
        $this->db->query('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $this->db->query('INSERT INTO test (name) VALUES (?)', ['Alice']);
        $this->db->query('INSERT INTO test (name) VALUES (?)', ['Bob']);

        $result = $this->db->query('SELECT name FROM test ORDER BY name');

        $rows = [];
        foreach ($result as $row) {
            $rows[] = $row['name'];
        }

        $this->assertSame(['Alice', 'Bob'], $rows);
    }

    public function testQueryWithParameterBinding(): void
    {
        $this->db->query('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $this->db->query('INSERT INTO test (name) VALUES (?)', ['Alice']);
        $this->db->query('INSERT INTO test (name) VALUES (?)', ['Bob']);

        $result = $this->db->query('SELECT name FROM test WHERE name = ?', ['Alice']);

        $rows = [];
        foreach ($result as $row) {
            $rows[] = $row['name'];
        }

        $this->assertSame(['Alice'], $rows);
    }

    public function testGetPdoReturnsUnderlyingConnection(): void
    {
        $pdo = $this->db->getPdo();

        $this->assertInstanceOf(\PDO::class, $pdo);
    }
}
