<?php

namespace CloudCastle\Core\Api\Common\DB;

use CloudCastle\Core\Api\Resources\PaginateResource;
use PDO;
use PDOStatement;
use stdClass;

final class PdoConnect
{
    /**
     * int
     */
    private const int FETCH_MODE = PDO::FETCH_OBJ;
    
    /**
     * @var PDO
     */
    private PDO $pdo;
    
    /**
     * @param array $config
     */
    public function __construct (array $config)
    {
        $this->pdo = new PDO(...$config['connect_params']);
    }
    
    /**
     * @param string $sql
     * @param array $binds
     * @param array $data
     * @return array
     */
    public function paginate (string $sql, array $binds, array $data): array
    {
        $limit = (int) $data['per_page'] ?? 50;
        $offset = (((int) $data['page'] ?? 1) - 1) * $limit;
        $stmt = $this->getPDO()->prepare("SELECT\n\tCOUNT(*) as total\nFROM ({$sql}) as paginate");
        $stmt->execute($binds);
        $total = $stmt->fetch(self::FETCH_MODE)->total ?? 0;
        
        if ($limit > 0) {
            $sql .= "LIMIT {$limit}\n";
            
            if ($offset > 0) {
                $sql .= "OFFSET {$offset}\n";
            }
        }
        
        $paginated = [
            'total' => $total,
            'page' => $data['page'],
            'per_page' => $limit,
        ];
        
        return ['collection' => $this->get($sql, $binds), 'paginate' => PaginateResource::make($paginated)];
    }
    
    /**
     * @return PDO
     */
    public function getPDO (): PDO
    {
        return $this->pdo;
    }
    
    /**
     * @param string $sql
     * @param array $binds
     * @return array<stdClass>
     */
    public function get (string $sql, array $binds): array
    {
        $stmt = $this->query($sql, $binds);
        $data = [];
        
        while ($row = $stmt->fetch(self::FETCH_MODE)) {
            $data[] = $row;
            unset($row);
        }
        
        return $data;
    }
    
    /**
     * @param string $sql
     * @param array $binds
     * @return PDOStatement
     */
    public function query (string $sql, array $binds): PDOStatement
    {
        $stmt = $this->getPDO()->prepare($sql);
        $stmt->execute($binds);
        
        return $stmt;
    }
    
    /**
     * @param string $sql
     * @param array $binds
     * @return object|null
     */
    public function first (string $sql, array $binds): object|null
    {
        return $this->query($sql, $binds)->fetch(self::FETCH_MODE) ?: null;
    }
}