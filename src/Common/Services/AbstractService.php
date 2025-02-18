<?php

namespace CloudCastle\Core\Api\Common\Services;

use CloudCastle\Core\Api\Common\Config\Config;
use CloudCastle\Core\Api\Common\DB\AbstractBuilder;
use CloudCastle\Core\Api\Common\DB\PdoConnect;
use CloudCastle\Core\Api\Common\Log\Log;
use Exception;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use stdClass;

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
            $pdo->beginTransaction();
            $builder = $this->filter::insert($data, $this->getTable());
            
            if ($pdo->query($builder->sql, $builder->binds)->rowCount() > 0) {
                $this->clearCache(["{$this->getTable()}:collection:*", "{$this->getTable()}:filters:*"]);
                $entity = $this->view($data['uuid']);
                $this->writeHistory([], (array) $entity, 'create');
                $pdo->commit();
                
                return $entity;
            }
        }catch(Exception $e){
            $pdo->rollBack();
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
     * @param string $uuid
     * @param string $trashed
     * @return object|null
     * @throws ServiceException
     */
    public function view (string $uuid, string|null $trashed = null): object|null
    {
        $pdo = $this->db->getPdo();
        
        try {
            $key = $this->getTable() . ':uuid:' . $uuid;
            
            if($entity = $this->getCache($key)) {
                return ((object)$entity??null);
            }
            
            $filters = [
                'id' => $uuid,
                'int_id' => $uuid,
            ];
            
            if($trashed) {
                $filters['trashed'] = $trashed;
            }
            
            $pdo->beginTransaction();
            $dbType = $this->config->database->{$this->dbName}['db_type'];
            $builder = $this->filter::select($filters, $this->getTable(), $this->getTableAlias(), $dbType);
            
            if ($entity = $this->db->first($builder->sql, $builder->binds)) {
                $this->setCache($key);
                $before = $after = (array) $entity;
                $this->writeHistory($before, $after, 'view');
                $pdo->commit();
                
                return $entity;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            Log::write($e, $this->getTable().'.log');
            $code = $e->getCode();
            $this->error[$code]['trace'] = $e->getTrace();
            $this->error[$code]['message'] = $e->getMessage();
        }
        
        return null;
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
    private function writeHistory (array $before, array $after, string $action): void
    {
        $table = $this->getTable();
        $historyConfig = $this->config->history;
        
        if ($historyConfig->status && isset($historyConfig->actions->{$action}->{$table}) && $historyConfig->actions->{$action}->{$table}) {
            $sql = /** @lang text */
                "INSERT INTO\n\thistories\n\t(uuid, table, service, action, before, after)\n";
            $sql .= "VALUES\n\t(:uuid, :table, :service, :action, :before, :after)\n";
            $binds = [
                ':uuid' => Uuid::uuid6()->toString(),
                ':table' => $table,
                ':service' => $this::class,
                ':action' => $action,
                ':before' => json_encode($before),
                ':after' => json_encode($after),
            ];
            
            $this->db->query($sql, $binds);
        }
    }
    
    /**
     * @param array $data
     * @return array
     * @throws ServiceException
     */
    public function list (array $data): array
    {
        $pdo = $this->db->getPdo();
        $list = [];
        
        try{
            $key = $this->getTable().':collection:' . md5(json_encode($data));
            
            if($list = $this->getCache($key)){
                return $list;
            }
            
            $pdo->beginTransaction();
            $dbType = ($this->config->database->{$this->dbName})['db_type'];
            $builder = $this->filter::select($data, $this->getTable(), $this->getTableAlias(), $dbType);
            [$collection, $paginate] = $this->db->paginate($builder->sql, $builder->binds, $data);
            $list = ['collection' => $collection, 'paginate' => $paginate];
            $this->setCache($key);
            $before = $after = $collection;
            $this->writeHistory($before, $after, 'list');
            $pdo->commit();
        }catch(Exception $e){
            $pdo->rollBack();
            Log::write($e, $this->getTable().'.log');
            $code = $e->getCode();
            $this->error[$code]['trace'] = $e->getTrace();
            $this->error[$code]['message'] = $e->getMessage();
        }
        
        return $list;
    }
    
    /**
     * @param array $uuids
     * @return array
     * @throws ServiceException
     */
    public function restore_group (array $uuids): array
    {
        $data = [];
        
        foreach ($uuids as $uuid) {
            $data[$uuid] = $this->restore($uuid, 'restore_group');
        }
        
        return $data;
    }
    
    /**
     * @param string $uuid
     * @param string $action
     * @return object|null
     * @throws ServiceException
     */
    public function restore (string $uuid, string $action = 'restore'): object|null
    {
        $pdo = $this->db->getPdo();
        
        try{
            $pdo->beginTransaction();
            [$before, $builder, $data] = $this->getCrudOptions($uuid, 'restore');
            
            if ($pdo->query($builder->sql, $builder->binds)->rowCount() > 0) {
                $this->clearCache([$this->getTable() . ':uuid:' . $uuid, $this->getTable() . ':collection:*', $this->getTable() . ':filters:*',]);
                $entity = $this->view($data['uuid'], 'all');
                $after = (array) $entity;
                $this->writeHistory($before, $after, $action);
                $pdo->commit();
                
                return $entity;
            }
        }catch(Exception $e){
            $pdo->rollBack();
            Log::write($e, $this->getTable().'.log');
            $code = $e->getCode();
            $this->error[$code]['trace'] = $e->getTrace();
            $this->error[$code]['message'] = $e->getMessage();
        }
        
        return null;
    }
    
    /**
     * @param string $uuid
     * @param string $action
     * @return array
     * @throws ServiceException
     */
    private function getCrudOptions (string $uuid, string $action): array
    {
        $data = ['uuid' => $uuid, 'trashed' => 'all'];
        $dbType = $this->config->database->{$this->dbName}['db_type'];
        $builder = $this->filter::select($data, $this->getTable(), $this->getTableAlias(), $dbType);
        $before = $this->db->first($builder->sql, $builder->binds);
        unset($builder);
        $builder = $this->filter::$action($uuid, $this->getTable());
        
        return [$before, $builder, $data];
    }
    
    /**
     * @param array $uuids
     * @return array
     * @throws ServiceException
     */
    public function soft_delete_group (array $uuids): array
    {
        $data = [];
        
        foreach ($uuids as $uuid) {
            $data[$uuid] = $this->soft_delete($uuid, 'soft_delete_group');
        }
        
        return $data;
    }
    
    /**
     * @param string $uuid
     * @param string $action
     * @return bool
     * @throws ServiceException
     */
    public function soft_delete (string $uuid, string $action = 'soft_delete'): bool
    {
        $pdo = $this->db->getPdo();
        
        try{
            $pdo->beginTransaction();
            [$before, $builder] = $this->getCrudOptions($uuid, 'soft_delete');
            
            return $this->delete($before, $builder, $action, $uuid);
        }catch(Exception $e){
            $pdo->rollBack();
            Log::write($e, $this->getTable().'.log');
            $code = $e->getCode();
            $this->error[$code]['trace'] = $e->getTrace();
            $this->error[$code]['message'] = $e->getMessage();
        }
        
        return false;
    }
    
    /**
     * @param array $uuids
     * @return array
     * @throws ServiceException
     */
    public function hard_delete_group (array $uuids): array
    {
        $data = [];
        
        foreach ($uuids as $uuid) {
            $data[$uuid] = $this->hard_delete($uuid, 'hard_delete_group');
        }
        
        return $data;
    }
    
    /**
     * @param string $uuid
     * @param string $action
     * @return bool
     * @throws ServiceException
     */
    public function hard_delete (string $uuid, string $action = 'hard_delete'): bool
    {
        $pdo = $this->db->getPdo();
        
        try{
            [$before, $builder] = $this->getCrudOptions($uuid, 'hard_delete');
            
            return $this->delete($before, $builder, $action, $uuid);
        }catch(Exception $e){
            $pdo->rollBack();
            Log::write($e, $this->getTable().'.log');
            $code = $e->getCode();
            $this->error[$code]['trace'] = $e->getTrace();
            $this->error[$code]['message'] = $e->getMessage();
        }
        
        return false;
    }
    
    /**
     * @param string $uuid
     * @param array $data
     * @return object|null
     * @throws ServiceException
     */
    public function update (string $uuid, array $data = []): object|null
    {
        $pdo = $this->db->getPdo();
        
        try{
            $pdo->beginTransaction();
            $data['uuid'] = $uuid;
            $before = (array) $this->view($data['uuid']);
            $builder = $this->filter::update($data, $this->getTable());
            
            if ($pdo->query($builder->sql, $builder->binds)->rowCount() > 0) {
                $this->clearCache([$this->getTable() . ':uuid:' . $uuid, $this->getTable() . ':collection:*', $this->getTable() . ':filters:*',]);
                $entity = $this->view($data['uuid']);
                $after = (array) $entity;
                $this->writeHistory($before, $after, 'update');
                $pdo->commit();
                
                return $entity;
            }
        }catch(Exception $e){
            $pdo->rollBack();
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
     * @param mixed $uuid
     * @return bool
     * @throws ServiceException
     */
    private function delete (mixed $before, mixed $builder, string $action, mixed $uuid): bool
    {
        $db = $this->db;
        
        if ($db->query($builder->sql, $builder->binds)->rowCount() > 0) {
            $this->writeHistory($before, $this->view($uuid, 'all'), $action);
            $this->clearCache([
                $this->getTable() . ':uuid:' . $uuid,
                $this->getTable() . ':collection:*',
                $this->getTable() . ':filters:*',
            ]);
            $db->getPdo()->commit();
            
            return true;
        }
        
        return false;
    }
}