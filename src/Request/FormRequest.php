<?php

namespace CloudCastle\Core\Api\Request;

use CloudCastle\Core\Api\Common\DB\PdoConnect;
use CloudCastle\Core\Api\Helpers\Hash;
use DateTime;
use stdClass;

abstract class FormRequest extends stdClass
{
    /**
     * @var array|string[]
     */
    protected array $common_rules = [
        'trashed' => 'default:not_trashed|string|nullable|enum:trashed,not_trashed,all',
        'id' => 'int|nullable',
        'uuid' => 'string|nullable',
        'created_at' => 'date|nullable',
        'updated_at' => 'date|nullable',
        'deleted_at' => 'date|nullable',
        'page' => 'nullable|default:1|int|min:1',
        'per_page' => 'nullable|default:50|int|min:0|max:100',
        'search' => 'string|nullable',
    ];
    
    /**
     * @var array
     */
    private array $errors = [];
    
    /**
     * @var array
     */
    private array $validData = [];
    
    /**
     *
     */
    final public function __construct ()
    {
        foreach (Request::getInstance() as $key => $value) {
            $this->{$key} = $value;
        }
    }
    
    /**
     * @return array
     * @throws ValidateException
     */
    final public function validate (): array
    {
        foreach ($this->getAllRules() as $key => $rules) {
            if (property_exists($this, $key)) {
                if (!$rules) {
                    $this->setErrorMessage($key, "Request rules missing for field ':param'", [':param' => $key]);
                    continue;
                }
                
                foreach (explode('|', trim($rules, '|')) as $rule) {
                    $this->runValidate($key, $rule);
                }
                
                if (isset($this->{$key})) {
                    Request::getInstance()->{$key} = $this->validData[$key] = $this->{$key};
                }
            } else {
                $this->setErrorMessage($key, "Param ':param' does not exist", [':param' => $key]);
            }
            
            if (str_contains($rules, 'nullable')) {
                $this->nullable($key);
            }
        }
        
        if (count($this->errors) === 0) {
            return $this->validData;
        }
        
        throw new ValidateException($this->getErrorMessage(), 1001);
    }
    
    /**
     * @return array
     */
    private function getAllRules (): array
    {
        return [
            ...$this->common_rules,
            ...$this->rules(),
        ];
    }
    
    /**
     * @return array
     */
    abstract public function rules (): array;
    
    /**
     * @param int|string $key
     * @param string $message
     * @param array $add
     * @return void
     */
    private function setErrorMessage (int|string $key, string $message, array $add): void
    {
        $this->errors[$key][] = trans('validate.' . trim($message), $add);
    }
    
    /**
     * @param int|string $key
     * @param $rule
     * @return void
     * @throws ValidateException
     */
    private function runValidate (int|string $key, $rule): void
    {
        if (str_contains($rule, ':')) {
            $rule = explode(':', $rule);
            $method = $rule[0];
            $params = explode(',', $rule[1] ?? '');
        } else {
            $method = $rule;
            $params = [];
        }
        
        if (method_exists($this, $method)) {
            $this->{$method}($key, ...$params);
        } else {
            throw new ValidateException("Method '".$method."' does not exist");
        }
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function nullable (string $key): void
    {
        unset($this->errors[$key]);
        
        if (!isset($this->{$key}) || $this->{$key} === '') {
            unset($this->{$key});
        }
    }
    
    /**
     * @return string
     */
    private function getErrorMessage (): string
    {
        $str = trans("validate.Validation failed !!!") . PHP_EOL;
        
        foreach ($this->getErrors() as $propertyName => $errors) {
            $str .= "\t".$propertyName.": " . PHP_EOL;
            
            foreach ($errors as $error) {
                $str .= "\t\t".$error . PHP_EOL;
            }
        }
        
        return $str;
    }
    
    /**
     * @return array
     */
    public function getErrors (): array
    {
        return array_map(function ($error){
            return array_unique($error);
        }, $this->errors);
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function str (string $key): void
    {
        $this->string($key);
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function string (string $key): void
    {
        if (isset($this->{$key})) {
            settype($this->{$key}, 'string');
        } else {
            $this->setErrorMessage($key, "Param ':param' must be string", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function integer (string $key): void
    {
        $this->int($key);
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function int (string $key): void
    {
        if (is_numeric($this->{$key})) {
            settype($this->{$key}, 'integer');
        } else {
            $this->setErrorMessage($key, "Param ':param' must be integer", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function double (string $key): void
    {
        $this->float($key);
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function float (string $key): void
    {
        if (is_numeric($this->{$key})) {
            settype($this->{$key}, 'float');
        } else {
            $this->setErrorMessage($key, "Param ':param' must be number", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function object (string $key): void
    {
        $this->array($key);
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function array (string $key): void
    {
        if (!is_array($this->{$key})) {
            $this->setErrorMessage($key, "The parameter :param must be an array or an object", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function boolean (string $key): void
    {
        $this->bool($key);
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function bool (string $key): void
    {
        $true = ['true', 'yes', 'on', '1', 1, true];
        
        if (in_array($this->{$key}, $true, true)) {
            $this->{$key} = true;
            return;
        }
        
        $false = ['false', 'no', 'off', '0', 0, false];
        
        if (in_array($this->{$key}, $false, true)) {
            $this->{$key} = false;
            return;
        }
        
        if (!is_bool($this->{$key})) {
            $this->setErrorMessage($key, "Param ':param' must be boolean. Acceptable values: :values",
                [
                    ':param' => $key,
                    ':values' => implode(', ', [...$true, ...$false]),
                ]
            );
        }
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function year (string $key): void
    {
        $this->date($key);
        $this->{$key} = (int) $this->{$key}->format('Y');
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function date (string $key): void
    {
        $this->string($this->{$key});
        
        if ($date = date_create($this->{$key})) {
            $this->{$key} = $date;
        } else {
            $this->setErrorMessage($key, "Param ':param' must be a valid date", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function year_day (string $key): void
    {
        $this->date($key);
        $yearDays = $this->year_days($this->{$key}->format('Y'));
        $this->{$key} = (int) getdate(strtotime($this->{$key}->format('Y-m-d')))['yday'] + 1;
        $this->min($key, 1);
        $this->max($key, $yearDays);
    }
    
    /**
     * @param int $year
     * @return int
     */
    private function year_days (int $year): int
    {
        return date("L", strtotime($year."-01-01")) ? 366 : 365;
    }
    
    /**
     * @param string $key
     * @param string $min
     * @return void
     */
    private function min (string $key, string $min): void
    {
        $length = $this->getLength($key, $min);
        
        if ($length < $min) {
            if ($this->{$key} instanceof DateTime) {
                $min = date('Y-m-d H:i:s', $min);
            }
            
            $this->setErrorMessage($key, "Param ':param' must be greater than :min", [':param' => $key, ':min' => $min]);
        }
    }
    
    /**
     * @param string $key
     * @param string $req
     * @return int
     */
    private function getLength (string $key, string &$req): int
    {
        $length = 0;
        
        if (is_array($this->{$key})) {
            $length = (float) count($this->{$key});
        } elseif (is_numeric($this->{$key})) {
            $length = (float) $this->{$key};
        } elseif (is_string($this->{$key})) {
            $length = (float) mb_strlen($this->{$key});
        } elseif (($file = ($this->{$key} ?? ($this->files[$key] ?? null))) && ($this->{$key} = $file) && ($this->{$key} instanceof UploadFile)) {
            $length = $this->{$key}->size;
        } elseif ($this->{$key} instanceof DateTime) {
            $length = $this->{$key}->getTimestamp();
            $req = date_create($req)->getTimestamp();
        } else {
            $this->setErrorMessage($key, "Param ':param' must be integer, float, string or array", [':param' => $key]);
        }
        
        $req = (float) $req;
        
        return $length;
    }
    
    /**
     * @param string $key
     * @param string $max
     * @return void
     */
    private function max (string $key, string $max): void
    {
        $length = $this->getLength($key, $max);
        
        if ($length > $max) {
            if ($this->{$key} instanceof DateTime) {
                $max = date('Y-m-d H:i:s', $max);
            }
            
            $this->setErrorMessage($key, "Param ':param' must be greater than :max", [':param' => $key, ':max' => $max]);
        }
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function month (string $key): void
    {
        $this->date($key);
        $this->{$key} = (int) $this->{$key}->format('m');
        $this->min($key, 1);
        $this->max($key, 12);
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function month_day (string $key): void
    {
        $this->date($key);
        $currentYear = $this->{$key}->format('Y');
        $currentMonth = $this->{$key}->format('m');
        $this->{$key} = (int) $this->{$key}->format('d');
        $this->min($key, 1);
        $this->max($key, cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear));
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function week (string $key): void
    {
        $this->date($key);
        $this->{$key} = (int) $this->{$key}->format('W');
        $this->min($key, 1);
        $this->max($key, 54);
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function week_day (string $key): void
    {
        $this->date($key);
        $this->{$key} = (int) $this->{$key}->format('N');
        $this->min($key, 1);
        $this->max($key, 7);
    }
    
    /**
     * @param string $key
     * @param string $format
     * @return void
     */
    private function date_time (string $key, string $format = 'Y-m-d H:i:s'): void
    {
        $this->string($this->{$key});
        
        if ($date = date_create($this->{$key})) {
            $this->{$key} = $date->format($format);
        } else {
            $this->setErrorMessage($key, "Param ':param' must be a valid date", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function time (string $key): void
    {
        $this->string($this->{$key});
        
        if ($time = date_create($this->{$key})) {
            $this->{$key} = $time->format('H:i:s');
        } else {
            $this->setErrorMessage($key, "Param ':param' must be a valid time", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function timestamp (string $key): void
    {
        $this->string($this->{$key});
        
        if ($timestamp = date_create($this->{$key})) {
            $this->{$key} = $timestamp->getTimestamp();
        } else {
            $this->setErrorMessage($key, "Param ':param' must be a valid date", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function email (string $key): void
    {
        if (!filter_var($this->{$key}, FILTER_VALIDATE_EMAIL)) {
            $this->setErrorMessage($key, "Param ':param' must be a valid email", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function url (string $key): void
    {
        if (!filter_var($this->{$key}, FILTER_VALIDATE_URL)) {
            $this->setErrorMessage($key, "Param ':param' must be a valid URL", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function ip_v4 (string $key): void
    {
        if (!filter_var($this->{$key}, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->setErrorMessage($key, "Param ':param' must be a valid IP V4", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function ip_v6 (string $key): void
    {
        if (!filter_var($this->{$key}, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->setErrorMessage($key, "Param ':param' must be a valid IP V6", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function domain (string $key): void
    {
        if (!filter_var($this->{$key}, FILTER_VALIDATE_DOMAIN)) {
            $this->setErrorMessage($key, "Param ':param' must be a valid domain", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @param string $format
     * @return void
     */
    private function date_format (string $key, string $format = 'Y-m-d H:i:s'): void
    {
        $this->string($this->{$key});
        
        if ($date = date_create($this->{$key})) {
            $this->{$key} = $date->format($format);
        } else {
            $this->setErrorMessage($key, "Param ':param' must be a valid date", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @param string|null $table
     * @param string $field
     * @param string|null $dbName
     * @return void
     */
    private function exists (string $key, string $table = null, string $field = 'id', string|null $dbName = 'default'): void
    {
        $config = $this->getDbConfig($key, $dbName, $table);
        
        if (!$config) {
            $this->setErrorMessage($key, "Configuration for database connection ':db_name' not found", [':param' => $key, ':db_name' => $dbName]);
        } elseif ($db = new PdoConnect($config)) {
            $sql = /** @lang text */
                "SELECT COUNT(*) AS entity FROM ".$table." WHERE ".$field." = ?";
            
            if (!$db->first($sql, [$field])) {
                $this->setErrorMessage($key, "Entry in table :table with parameters :param not found",
                    [
                        ':param' => $field." = '".$this->{$key}."'",
                        ':table' => $table
                    ]
                );
            }
        } else {
            $this->setErrorMessage($key, "DB connection :param is not exists", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @param string|null $dbName
     * @param string|null $table
     * @return array|null
     */
    private function getDbConfig (string $key, string|null $dbName, ?string $table): array|null
    {
        if (!$table) {
            $this->setErrorMessage($key, "The table name in the database is specified incorrectly", [':param' => $key]);
        }
        
        if (!$dbName) {
            $this->setErrorMessage($key, "The database connection name is specified incorrectly", [':param' => $key]);
        }
        
        return config("database")[$dbName] ?? null;
    }
    
    /**
     * @param string $key
     * @param string|null $table
     * @param string $field
     * @param string|null $dbName
     * @return void
     */
    private function unique (string $key, string $table = null, string $field = 'id', string|null $dbName = 'default'): void
    {
        $config = $this->getDbConfig($key, $dbName, $table);
        
        if (!$config) {
            $this->setErrorMessage($key, "Configuration for database connection ':db_name' not found", [':param' => $key, ':db_name' => $dbName]);
        } elseif ($db = new PdoConnect($config)) {
            $sql = /** @lang text */
                "SELECT COUNT(*) AS entity FROM ".$table." WHERE ".$field." = ?";
            
            if ($db->first($sql, [$field])) {
                $this->setErrorMessage($key, "Entry in table :table with parameters :param already exists",
                    [
                        ':param' => $field." = '".$this->{$key}."'",
                        ':table' => $table
                    ]
                );
            }
        } else {
            $this->setErrorMessage($key, "DB connection :param is not exists", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @param ...$ext
     * @return void
     */
    private function file (string $key, ...$ext): void
    {
        if (!isset($this->files[$key]) || !($this->files[$key] instanceof UploadFile)) {
            $this->setErrorMessage($key, "File ':param' upload error", [':param' => $key]);
            return;
        }
        
        if (($file = $this->files[$key]) && $ext && !in_array($file->info->ext, $ext)) {
            $this->setErrorMessage($key, "The file ':param' mime type does not match the given parameters" .
                " Allowed file types: :ext", [':param' => $key, ':ext' => implode(', ', $ext)]
            );
            return;
        }
        
        $this->{$key} = $file;
    }
    
    /**
     * @param string $key
     * @param ...$ext
     * @return void
     */
    private function files (string $key, ...$ext): void
    {
        if (($list = $this->files[$key] ?? null) && is_array($list)) {
            foreach ($list as $i => $file) {
                if ($file instanceof UploadFile) {
                    if ($file->error) {
                        $this->setErrorMessage($key . "[".$i."]", "File ':param' upload error. Error code: :code", [':param' => $key, ':code' => $file->error]);
                        continue;
                    }
                    
                    if ($ext && !in_array($file->info->ext, $ext)) {
                        $this->setErrorMessage($key . "[".$i."]", "The file ':param' mime type does not match the given parameters\n" .
                            "Allowed file types: :ext", [':param' => $key . "[".$i."]", ':ext' => implode(', ', $ext)]);
                    } else {
                        $this->{$key}[$i] = $file;
                    }
                }
            }
        } else {
            $this->setErrorMessage($key, "Param ':param' must be an list files", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @param string|null $alg
     * @return void
     */
    private function password (string $key, string|null $alg = null): void
    {
        $this->required($key);
        $this->min($key, 6);
        $this->max($key, 20);
        
        if (!$alg) {
            $alg = config('app')['password_hash_algo'];
        }
        
        $this->{$key} = (object) [
            'input' => $this->{$key},
            'hash' => Hash::make((string) $this->{$key}, $alg),
            'alg' => $alg,
        ];
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function required (string $key): void
    {
        if (!property_exists($this, $key) || $this->{$key} === null || $this->{$key} === '') {
            $this->setErrorMessage($key, "Param ':param' is required. Can't be empty", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function json (string $key): void
    {
        $this->required($key);
        
        if (json_validate($this->{$key})) {
            $this->{$key} = json_decode($this->{$key});
        } else {
            $this->setErrorMessage($key, "Param ':param' not valid JSON string", [':param' => $key]);
        }
    }
    
    /**
     * @param string $key
     * @return void
     */
    private function escape_tags (string $key): void
    {
        $this->string($this->{$key});
        $this->{$key} = htmlspecialchars($this->{$key});
    }
    
    /**
     * @param string $key
     * @param mixed $default
     * @return void
     */
    private function default (string $key, mixed $default): void
    {
        if (!property_exists($this, $key)) {
            $this->{$key} = $default;
        }
    }
    
    /**
     * @param string $key
     * @param ...$values
     * @return void
     */
    private function enum (string $key, ...$values): void
    {
        if (!in_array($key, $values)) {
            $this->setErrorMessage($key, "Field value :param must have one of the values :values",
                [
                    ':param' => $key,
                    ':values' => implode(', ', $values)
                ]
            );
        }
    }
}