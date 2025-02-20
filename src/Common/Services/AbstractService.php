<?php

namespace CloudCastle\Core\Api\Common\Services;

use CloudCastle\Core\Api\Common\Auth\Auth;
use CloudCastle\Core\Api\Common\Config\Config;
use CloudCastle\Core\Api\Common\DB\AbstractBuilder;
use CloudCastle\Core\Api\Common\DB\PdoConnect;
use CloudCastle\Core\Api\Common\Log\Log;
use Exception;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use stdClass;
use Throwable;

/**
 * @property PdoConnect $db
 * @property AbstractBuilder::class $filter
 */
abstract class AbstractService extends stdClass
{
    /**
     * @var string
     */
    protected string $dbName = 'default';
    
    /**
     * @var string|null
     */
    protected string|null $table = null;
    
    /**
     * @var Config|mixed
     */
    protected readonly Config $config;
    
    /**
     * @var PdoConnect
     */
    protected PdoConnect|null $db = null;
    
    /**
     * @var array
     */
    protected array $error = [];
    
    /**
     * @throws ReflectionException
     * @throws ServiceException
     */
    public function __construct ()
    {
        $this->config = config();
        
        if (!$this->filter || !in_array(AbstractBuilder::class, getClassImplements($this->filter))) {
            throw new ServiceException('service.Filter must be defined or not implemented ' . AbstractBuilder::class);
        }
        
        foreach ($this->di() as $key => $value) {
            $this->{$key} = $value;
        }
    }
    
    abstract protected function di (): array;
    
    /**
     * @return array
     */
    public function getErrors (): array
    {
        return $this->error;
    }
    
    /**
     * @param array $data
     * @return object|null
     * @throws ServiceException
     */
    public function create (array $data = []): object|null
    {
        $pdo = $this->db->getPdo();
        
        try{
            $dbType = $this->config->database->{$this->dbName}['db_type'];
            $builder = $this->filter::insert($data, $this->getTable(), $dbType);
            $stmt = $pdo->prepare($builder->sql);
            $stmt->execute($builder->binds);
            
            if ($stmt->rowCount() > 0) {
                $this->clearCache(["{$this->getTable()}:collection:*", "{$this->getTable()}:filters:*"]);
                $entity = $this->view($data['id']);
                $this->writeHistory([], (array) $entity, 'create');
                
                return $entity;
            }
        }catch(Exception $e){
            Log::write($e, $this->getTable().'.log');
            $code = $e->getCode();
            $this->error[$code]['trace'] = $e->getTrace();
            $this->error[$code]['message'] = $e->getMessage();
        }
        
        return null;
    }
    
    /**
     * @return string
     * @throws ServiceException
     */
    final public function getTable (): string
    {
        if (!$this->table) {
            throw new ServiceException(trans('service.Table name not set'));
        }
        
        return $this->table;
    }
    
    /**
     * @param array $keys
     * @return void
     */
    private function clearCache (array $keys): void
    {
        if($config = $this->getCacheConfig()) {
        
        }
    }
    
    /**
     * @param string $id
     * @param string $trashed
     * @return object|null
     * @throws ServiceException
     */
    public function view (string $id, string|null $trashed = null): object|null
    {
        try {
            $key = $this->getTable() . ':id:' . $id;
            
            if($entity = $this->getCache($key)) {
                return ((object)$entity??null);
            }
            
            $filters = [
                'id' => $id,
            ];
            
            if($trashed) {
                $filters['trashed'] = $trashed;
            }
            
            $dbType = $this->config->database->{$this->dbName}['db_type'];
            $builder = $this->filter::select($filters, $this->getTable(), $this->getTableAlias(), $dbType);
            
            if ($entity = $this->db->first($builder->sql, $builder->binds)) {
                $this->setCache($key);
                $before = $after = (array) $entity;
                $this->writeHistory($before, $after, 'view');
                
                return $entity;
            }
        } catch (Exception $e) {
            Log::write($e, $this->getTable().'.log');
            $code = $e->getCode();
            $this->error[$code]['trace'] = $e->getTrace();
            $this->error[$code]['message'] = $e->getMessage();
        }
        
        page_not_found();
    }
    
    /**
     * @return string|null
     */
    protected function getTableAlias (): string|null
    {
        return $this->tableAlias ?? null;
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function setCache (string $key): void
    {
        if ($config = $this->getCacheConfig()) {
        
        }
    }
    
    private function getCacheConfig (): stdClass|null
    {
        if (($config = $this->config->cache) && $config->status) {
            return $config;
        }
        
        return null;
    }
    
    /**
     * @param array $before
     * @param array $after
     * @param string $action
     * @return void
     * @throws ServiceException
     */
    private function writeHistory (array|object $before, array|object $after, string $action): void
    {
        $table = $this->getTable();
        $historyConfig = $this->config->history;
        $configAction = $historyConfig->actions[$action][$table]??null;
        $user = Auth::user();
        
        $entityId = null;
        $after = (array) $after;
        $before = (array) $before;
        
        if(isset($before['id'])) {
            $entityId = $before['id'];
        }
        
        if(!$entityId && isset($after['id'])) {
            $entityId = $before['id'];
        }
        
        if ($historyConfig->status && $configAction) {
            $binds = [
                ':id' => Uuid::uuid6()->toString(),
                ':table_name' => $table,
                ':entity_id' => $entityId,
                ':service' => $this::class,
                ':action' => $action,
                ':before' => json_encode($before),
                ':after' => json_encode($after),
            ];
            
            if($user && $user->id){
                $binds[':user_id'] = $user->id;
            }
            
            $keys = array_keys($binds);
            $sql = /** @lang text */
                "INSERT INTO\n\thistories\n\t(".str_replace(':', '', implode(",\n\t", $keys)).")\n";
            $sql .= "VALUES\n\t(".implode(",\n\t", $keys).")\n";
            
            try{
                $this->db->query($sql, $binds);
            }catch(Throwable $e){
                Log::write($e, $this->getTable().'.log');
                $code = $e->getCode();
                $this->error[$code]['trace'] = $e->getTrace();
                $this->error[$code]['message'] = $e->getMessage();
            }
        }
    }
    
    /**
     * @param array $data
     * @return array
     * @throws ServiceException
     */
    public function list (array $data): array|null
    {
        $list = [];
        
        try{
            $key = $this->getTable().':collection:' . md5(json_encode($data));
            
            if($list = $this->getCache($key)){
                return $list;
            }
            
            $dbType = ($this->config->database->{$this->dbName})['db_type'];
            $builder = $this->filter::select($data, $this->getTable(), $this->getTableAlias(), $dbType);
            
            if($result = $this->db->paginate($builder->sql, $builder->binds, $data)){
                [$collection, $paginate] = $result;
                $list = ['collection' => $collection, 'paginate' => $paginate];
                $this->setCache($key);
                $before = $after = $collection;
                $this->writeHistory($before, $after, 'list');
            }
        }catch(Exception $e){
            Log::write($e, $this->getTable().'.log');
            $code = $e->getCode();
            $this->error[$code]['trace'] = $e->getTrace();
            $this->error[$code]['message'] = $e->getMessage();
        }
        
        return $list;
    }
    
    /**
     * @param array $ids
     * @return array
     * @throws ServiceException
     */
    public function restore_group (array $ids): array
    {
        $data = [];
        
        foreach ($ids as $id) {
            $data[$id] = $this->restore($id, 'restore_group');
        }
        
        return $data;
    }
    
    /**
     * @param string $id
     * @param string $action
     * @return object|null
     * @throws ServiceException
     */
    public function restore (string $id, string $action = 'restore'): object|null
    {
        try{
            [$before, $builder, $data] = $this->getCrudOptions($id, 'restore');
            
            if ($this->db->query($builder->sql, $builder->binds)->rowCount() > 0) {
                $this->clearCache([$this->getTable() . ':id:' . $id, $this->getTable() . ':collection:*', $this->getTable() . ':filters:*',]);
                $entity = $this->view($data['id'], 'all');
                $after = (array) $entity;
                $this->writeHistory($before, $after, $action);
                
                return $entity;
            }
        }catch(Exception $e){
            Log::write($e, $this->getTable().'.log');
            $code = $e->getCode();
            $this->error[$code]['trace'] = $e->getTrace();
            $this->error[$code]['message'] = $e->getMessage();
        }
        
        page_not_found();
    }
    
    /**
     * @param string $id
     * @param string $action
     * @return array
     * @throws ServiceException
     */
    private function getCrudOptions (string $id, string $action): array
    {
        $data = ['id' => $id, 'trashed' => 'all'];
        $dbType = $this->config->database->{$this->dbName}['db_type'];
        $builder = $this->filter::select($data, $this->getTable(), $this->getTableAlias(), $dbType);
        $before = $this->db->first($builder->sql, $builder->binds);
        
        unset($builder);
        $builder = $this->filter::$action($id, $this->getTable(), dbType: $dbType);
        
        return [$before, $builder, $data];
    }
    
    /**
     * @param array $ids
     * @return array
     * @throws ServiceException
     */
    public function soft_delete_group (array $ids): array
    {
        $data = [];
        
        foreach ($ids as $id) {
            try{
                $data[$id] = $this->soft_delete($id, 'soft_delete_group');
            }catch(Throwable $e){
                Log::write($e, $this->getTable().'.log');
                $code = $e->getCode();
                $this->error[$code]['trace'] = $e->getTrace();
                $this->error[$code]['message'] = $e->getMessage();
                $data[$id] = false;
            }
        }
        
        return $data;
    }
    
    /**
     * @param string $id
     * @param string $action
     * @return bool
     * @throws ServiceException
     */
    public function soft_delete (string $id, string $action = 'soft_delete'): bool
    {
        [$before, $builder] = $this->getCrudOptions($id, 'soft_delete');
            
        return (bool)$this->delete($before, $builder, $action, $id);
    }
    
    /**
     * @param array $ids
     * @return array
     * @throws ServiceException
     */
    public function hard_delete_group (array $ids): array
    {
        $data = [];
        
        foreach ($ids as $id) {
            try{
                $data[$id] = $this->hard_delete($id, 'hard_delete_group');
            }catch(Throwable $e){
                Log::write($e, $this->getTable().'.log');
                $code = $e->getCode();
                $this->error[$code]['trace'] = $e->getTrace();
                $this->error[$code]['message'] = $e->getMessage();
                $data[$id] = false;
            }
        }
        
        return $data;
    }
    
    /**
     * @param string $id
     * @param string $action
     * @return bool
     * @throws ServiceException
     */
    public function hard_delete (string $id, string $action = 'hard_delete'): bool
    {
        [$before, $builder] = $this->getCrudOptions($id, 'hard_delete');
            
        return (bool)$this->delete($before, $builder, $action, $id);
    }
    
    /**
     * @param string $id
     * @param array $data
     * @return object|null
     * @throws ServiceException
     */
    public function update (string $id, array $data = []): object|null
    {
        $pdo = $this->db->getPdo();
        
        try{
            $data['id'] = $id;
            $before = (array) $this->view($data['id']);
            $dbType = $this->config->database->{$this->dbName}['db_type'];
            $builder = $this->filter::update($data, $this->getTable(), $dbType);
            
            if ($this->db->query($builder->sql, $builder->binds)->rowCount() > 0) {
                $this->clearCache([$this->getTable() . ':id:' . $id, $this->getTable() . ':collection:*', $this->getTable() . ':filters:*',]);
                $entity = $this->view($data['id']);
                $after = (array) $entity;
                $this->writeHistory($before, $after, 'update');
                
                return $entity;
            }
        }catch(Exception $e){
            Log::write($e, $this->getTable().'.log');
            $code = $e->getCode();
            $this->error[$code]['trace'] = $e->getTrace();
            $this->error[$code]['message'] = $e->getMessage();
        }
        
        return null;
    }
    
    private function getCache (string $key): mixed
    {
        
        return null;
    }
    
    /**
     * @param mixed $before
     * @param mixed $builder
     * @param string $action
     * @param mixed $id
     * @return bool
     * @throws ServiceException
     */
    private function delete (mixed $before, mixed $builder, string $action, mixed $id): bool
    {
        $db = $this->db;
            
        if ($db->query($builder->sql, $builder->binds)->rowCount() > 0) {
            $this->writeHistory($before, $this->view($id, 'all'), $action);
            $this->clearCache([
                $this->getTable() . ':id:' . $id,
                $this->getTable() . ':collection:*',
                $this->getTable() . ':filters:*',
            ]);
                
            return true;
        }
        
        page_not_found();
    }
}