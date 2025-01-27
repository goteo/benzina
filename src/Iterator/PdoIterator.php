<?php

namespace Goteo\BenzinaBundle\Iterator;

class PdoIterator implements \Iterator
{
    private \PDOStatement $query;

    protected int $key = 0;

    protected mixed $result = null;

    protected bool $valid = true;

    public function __construct(
        string $database,
        private string $tablename,
    ) {
        $parsedUrl = parse_url($database);
        $dbdata = [
            'name' => ltrim($parsedUrl['path'], '/'),
            ...$parsedUrl,
        ];

        $pdo = new \PDO(
            dsn: sprintf('%s:host=%s;dbname=%s', $dbdata['scheme'], $dbdata['host'], $dbdata['name']),
            username: $dbdata['user'],
            password: $dbdata['pass'],
            options: [
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        );

        $this->query = $pdo->prepare(
            "SELECT * FROM `$tablename`",
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL],
        );

        $this->query->execute();
    }

    public function current(): int
    {
        return $this->result;
    }

    public function next(): void
    {
        $result = $this->query->fetch(
            \PDO::FETCH_BOTH,
            \PDO::FETCH_ORI_ABS,
            $this->key
        );

        $this->key++;

        if ($result === false) {
            $this->valid = false;
            return;
        }

        $this->result;
    }

    public function key(): int
    {
        return $this->key;
    }

    public function valid(): bool
    {
        return $this->valid;
    }

    public function rewind(): void
    {
        $this->key = 0;
    }
}
