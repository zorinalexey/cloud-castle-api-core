<?php

namespace CloudCastle\Core\Api\Common\DB;

use CloudCastle\Core\Api\Request\Request;
use DateTime;
use Ramsey\Uuid\Uuid;
use stdClass;

abstract class AbstractBuilder extends stdClass
{
    /**
     *
     */
    protected const array COMMON_FIELDS = [
        'id',
        'uuid',
        'deleted_at',
        'created_at',
        'updated_at',
    ];
    
    /**
     * @var string|null
     */
    protected readonly string|null $alias;
    
    /**
     * @var string
     */
    protected readonly string $table;
    
    /**
     * @var array|string[]
     */
    protected array $fillable = [];
    
    /**
     * @var array
     */
    protected array $selectFields = [];
    
    /**
     * @var array
     */
    protected array $joins = [];
    /**
     * @var array
     */
    protected array $groupBy = [];
    /**
     * @var array
     */
    protected array $having = [];
    /**
     * @var array
     */
    private array $conditions = [];
    /**
     * @var array
     */
    private array $orderBy = [];
    
    /**
     * @var array
     */
    private array $bindings = [];
    
    /**
     * @var string
     */
    private readonly string $dbType;
    
    /**
     * @param array $filters
     * @param string $table
     * @param string|null $alias
     * @param string|null $dbType
     */
    final protected function __construct (array $filters, string $table, string|null $alias = null, string|null $dbType = null)
    {
        $this->table = $table;
        $this->alias = $alias;
        $this->dbType = $dbType;
        $this->setAddFilters($filters);
        $this->checkSorts($filters);
        
        foreach ($filters as $method => $value) {
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
    }
    
    /**
     * @param array $filters
     * @return void
     */
    abstract protected function setAddFilters (array &$filters): void;
    
    private function checkSorts (array &$filters): void
    {
        if (!isset($filters['sort'])) {
            $fields = $this->getFieldsByCRUD();
            
            if (in_array('name', $fields)) {
                $filters['sort']['name'] = 'asc';
            } else {
                $filters['sort']['id'] = 'desc';
            }
        }
    }
    
    /**
     * @return array
     */
    final protected function getFieldsByCRUD (): array
    {
        return [
            ...$this->fillable,
            ...self::COMMON_FIELDS,
        ];
    }
    
    /**
     * @param array $filters
     * @param string $table
     * @param string|null $alias
     * @return object
     */
    public static function select (array $filters, string $table, string|null $alias = null, string|null $dbType = null): object
    {
        $instance = new static($filters, $table, $alias);
        
        return self::getResult($instance, $instance->getSqlSelectString());
    }
    
    /**
     * @param AbstractBuilder $instance
     * @param string $sql
     * @return object
     */
    private static function getResult (AbstractBuilder $instance, string $sql): object
    {
        return (object) [
            'sql' => $sql,
            'binds' => $instance->getBinds(),
        ];
    }
    
    /**
     * @return array
     */
    final protected function getBinds (): array
    {
        return $this->bindings;
    }
    
    /**
     * @return string
     */
    final protected function getSqlSelectString (): string
    {
        $this->setOtherSelectSqlOptions();
        $sql = "SELECT\n\t" . implode(",\n\t", $this->getSelectFields()) . "\n";
        $sql .= "FROM\n\t{$this->getTableAsAlias()}\n";
        
        if ($this->joins) {
            $sql .= implode("\n\t", $this->joins) . "\n";
        }
        
        if ($this->conditions) {
            $sql .= "WHERE\n\t" . $this->cleanConditions() . "\n";
        }
        
        if ($this->groupBy) {
            $sql .= implode("\n\t", $this->groupBy) . "\n";
        }
        
        if ($this->having) {
            $sql .= implode("\n\t", $this->having) . "\n";
        }
        
        if ($this->orderBy) {
            $sql .= implode("\n\t", $this->orderBy) . "\n";
        }
        
        return $sql;
    }
    
    protected function setOtherSelectSqlOptions (): void
    {
    }
    
    /**
     * @return array
     */
    final protected function getSelectFields (): array
    {
        $data = [];
        
        $fields = [
            ...$this->fillable,
            ...self::COMMON_FIELDS,
        ];
        
        if ($this->getAlias()) {
            foreach ($fields as $field) {
                $data[] = $this->getField($field);
            }
        } else {
            foreach ($fields as $field) {
                $data[] = "{$this->getTable()}.$field";
            }
        }
        
        $data = [...$data, ...$this->selectFields];
        
        return array_unique($data);
    }
    
    /**
     * @return string
     */
    final protected function getAlias (): string
    {
        return $this->alias;
    }
    
    /**
     * @param string $field
     * @return string
     */
    final protected function getField (string $field): string
    {
        if (str_contains(mb_strtolower($field), ' as ')) {
            return $field;
        }
        
        if (!$this->alias) {
            return "{$this->getTable()}.{$field}";
        }
        
        return "{$this->getAlias()}.{$field}";
    }
    
    /**
     * @return string
     */
    final protected function getTable (): string
    {
        return $this->table;
    }
    
    /**
     * @return string
     */
    final protected function getTableAsAlias (): string
    {
        if (!$this->alias) {
            return $this->getTable();
        }
        
        return "{$this->getTable()} AS {$this->getAlias()}";
    }
    
    /**
     * @return string
     */
    final protected function cleanConditions (): string
    {
        $clearing = [
            'AND ',
            'OR ',
            'and ',
            'or ',
        ];
        $conditions = implode("\n\t", $this->conditions);
        
        foreach ($clearing as $clear) {
            $conditions = trim($conditions, $clear);
        }
        
        return $conditions;
    }
    
    /**
     * @param string|int $id
     * @param string $table
     * @return object
     */
    public static function soft_delete (string|int $id, string $table): object
    {
        $instance = new static(['id' => $id], $table);
        $sql = /** @lang text */
            "UPDATE\n\t{$instance->getTable()}\nSET\n\t{$instance->getField('deleted_at')} = NOW()\n";
        $sql .= "WHERE\n\tid = {$instance->getBindName($id)}\n\t";
        $sql .= "OR\n\tuuid = {$instance->getBindName($id)}\n";
        
        return self::getResult($instance, $sql);
    }
    
    /**
     * @param string|array $value
     * @return string|array
     */
    final public function getBindName (string|array $value): string|array
    {
        if (is_array($value)) {
            $bindCollection = [];
            
            foreach ($value as $item) {
                $bindCollection[] = $this->getBindName($item);
            }
            
            return $bindCollection;
        }
        
        $name = ':bind_' . md5(serialize($value));
        $this->bindings[$name] = $value;
        
        return $name;
    }
    
    /**
     * @param string|int $id
     * @param string $table
     * @return object
     */
    public static function hard_delete (string|int $id, string $table): object
    {
        $instance = new static(['id' => $id], $table);
        $sql = /** @lang text */
            "DELETE FROM\n\t{$instance->getTable()}\n";
        $sql .= "WHERE\n\tid = {$instance->getBindName($id)}\n\t";
        $sql .= "OR\n\tuuid = {$instance->getBindName($id)}\n";
        
        return self::getResult($instance, $sql);
    }
    
    /**
     * @param string|int $id
     * @param string $table
     * @return object
     */
    public static function restore (string|int $id, string $table): object
    {
        $instance = new static(['id' => $id], $table);
        $sql = /** @lang text */
            "UPDATE\n\t{$instance->getTable()}\nSET\n\t{$instance->getField('deleted_at')} = NULL\n";
        $sql .= "WHERE\n\tid = {$instance->getBindName($id)}\n\t";
        $sql .= "OR\n\tuuid = {$instance->getBindName($id)}\n";
        
        return self::getResult($instance, $sql);
    }
    
    /**
     * @param array $data
     * @param string $table
     * @return object
     */
    public static function insert (array &$data, string $table): object
    {
        $instance = new static($data, $table);
        $fields = array_keys($instance->getFieldsByCRUD());
        $keys = [];
        $values = [];
        
        if (!isset($data['uuid'])) {
            $data['uuid'] = Uuid::uuid6()->toString();
        }
        
        if (!isset($data['created_at'])) {
            $data['created_at'] = (new DateTime())->format('Y-m-d H:i:s');
        }
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $keys[] = $instance->getField($field);
                $values[] = $instance->getBindName($data[$field]);
            }
        }
        
        $sql = /** @lang text */
            "INSERT INTO\n\t{$instance->getTable()}\n\t";
        $sql .= "(" . implode(",\n\t\t", $keys) . ") VALUES\n\t";
        $sql .= "(" . implode(",\n\t\t", $values) . ")\n";
        
        return self::getResult($instance, $sql);
    }
    
    /**
     * @param array $data
     * @param string $table
     * @return object
     * @throws BuilderException
     */
    public static function update (array $data, string $table): object
    {
        $instance = new static($data, $table);
        $fields = array_keys($instance->getFieldsByCRUD());
        $sets = [];
        $id = $data['uuid'] ?? ($data['id'] ?? throw new BuilderException(trans('builder.Field uuid or id is required')));
        unset($data['uuid'], $data['id'], $data['created_at']);
        
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = (new DateTime())->format('Y-m-d H:i:s');
        }
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $sets[] = "{$instance->getField($field)} = {$instance->getBindName($data[$field])}";
            }
        }
        
        $bindId = $instance->getBindName($id);
        $sql = /** @lang text */
            "UPDATE\n\t{$instance->getTable()}\nSET\n\t";
        $sql .= implode(",\n\t", $sets) . "\n";
        $sql .= "WHERE\n\tid = {$bindId}\n\tOR uuid = {$bindId}\n";
        
        return self::getResult($instance, $sql);
    }
    
    /**
     * @return void
     */
    final protected function trashed (): void
    {
        $trashed = (string) Request::getInstance()->trashed;
        $conditions = match ($trashed) {
            default => "AND {$this->getField('deleted_at')} IS NULL",
            'trashed' => "AND {$this->getField('deleted_at')} IS NOT NULL",
            'all' => '',
        };
        
        if ($conditions) {
            $this->setConditions($conditions);
        }
    }
    
    /**
     * @param string $condition
     * @return void
     */
    final protected function setConditions (string $condition): void
    {
        $this->conditions[md5($condition)] = trim($condition);
    }
    
    /**
     * @param string|int|array $ids
     * @return void
     */
    protected function id (string|int|array $ids): void
    {
        if (is_array($ids)) {
            $binds = $this->getBindName($ids);
            $conditions = "AND id IN\n\t(" . implode(",\n\t\t", $binds) . "\n\t)";
        } else {
            $conditions = "AND id = {$this->getBindName($ids)}";
        }
        
        $this->setConditions($conditions);
    }
    
    /**
     * @param string|array $uuids
     * @return void
     */
    final protected function uuid (string|array $uuids): void
    {
        if (is_array($uuids)) {
            $binds = $this->getBindName($uuids);
            $conditions = "AND uuid IN\n\t(" . implode(",\n\t\t", $binds) . "\n\t)";
        } else {
            $conditions = "AND uuid = {$this->getBindName($uuids)}";
        }
        
        $this->setConditions($conditions);
    }
    
    /**
     * @param DateTime $created_at
     * @return void
     */
    final protected function created_at (DateTime $created_at): void
    {
        $this->setConditions("AND created_at = {$this->getBindName($created_at->format('Y-m-d H:i:s'))}");
    }
    
    /**
     * @param DateTime $updated_at
     * @return void
     */
    final protected function updated_at (DateTime $updated_at): void
    {
        $this->setConditions("AND updated_at = {$this->getBindName($updated_at->format('Y-m-d H:i:s'))}");
    }
    
    /**
     * @param DateTime $deleted_at
     * @return void
     */
    final protected function deleted_at (DateTime $deleted_at): void
    {
        $this->setConditions("AND deleted_at = {$this->getBindName($deleted_at->format('Y-m-d H:i:s'))}");
    }
    
    /**
     * @param string|array $search
     * @return void
     */
    final protected function search (string|array $search): void
    {
        $condition = '';
        $values = explode(' ', $search);
        $fields = $this->getFieldsForSearch();
        
        foreach ($values as $value) {
            foreach ($fields as $field) {
                $condition .= "OR LOWER({$field}) LIKE {$this->getBindName(mb_strtolower($value))}\n\t";
            }
        }
        
        if ($condition) {
            $this->setConditions("AND (" . trim($condition, 'OR ') . ")");
        }
    }
    
    /**
     * @return array
     */
    private function getFieldsForSearch (): array
    {
        $fields = [];
        $dbTextFunc = match (mb_strtolower($this->dbType)) {
            'pgsql', 'postgres', 'postgesql' => ':field::TEXT',
            'mysql' => 'CONVERT(:field, CHAR)',
            'mssql' => 'CAST(:field AS NVARCHAR(MAX))',
            'sqlite' => 'CAST(:field AS TEXT)',
            default => null,
        };
        $patterns = [
            '~^([\w.]+) as (\w+)$~ui' => '$1, $2',
            '~^([\w.]+)$~ui' => '$1',
        ];
        
        foreach ($this->getSelectFields() as $field) {
            foreach ($patterns as $pattern => $replace) {
                if (preg_match($pattern, $field) && $dbTextFunc) {
                    $fields[] = str_replace(':field', preg_replace($pattern, $replace, $field), $dbTextFunc);
                }
            }
        }
        
        return $fields;
    }
    
    /**
     * @param string $groupBy
     * @return void
     */
    final protected function setGroupBy (string $groupBy): void
    {
        $this->groupBy[md5($groupBy)] = $groupBy;
    }
    
    /**
     * @param string $having
     * @return void
     */
    final protected function setHaving (string $having): void
    {
        $this->having[md5($having)] = $having;
    }
    
    final protected function sort (array $sorts): void
    {
        $fields = $this->getFieldsForSearch();
        
        foreach ($sorts as $field => $direction) {
            $method = 'sort_' . mb_strtolower($field);
            $direction = match (mb_strtoupper($direction)) {
                'DESC' => 'DESC',
                default => 'ASC',
            };
            
            if (method_exists($this, $method)) {
                $this->$method($direction);
            } else {
                if (in_array($field, $fields)) {
                    $this->setOrderBy($field . ' ' . $direction);
                }
            }
        }
    }
    
    /**
     * @param string $orderBy
     * @return void
     */
    final protected function setOrderBy (string $orderBy): void
    {
        $this->orderBy[md5($orderBy)] = $orderBy;
    }
}