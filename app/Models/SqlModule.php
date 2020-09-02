<?php

namespace App\Models;

class SqlModule extends BaseModule
{
    protected $fillable = ['driver', 'host', 'port', 'database', 'username', 'password'];

    public $timestamps = false;

    public function module()
    {
        return $this->morphOne(BaseModule::class, 'modulable');
    }

    public function connectionData()
    {
        $data = [
            'driver' => $this->driver,
            'host' => $this->host,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
        ];

        if ($this->driver !== 'sqlite') {
            $port = $this->port;
            if (!$port) {
                switch ($this->driver) {
                    case 'pgsql':
                        $port = '5432';
                        break;
                    case 'sqlsrv':
                        $port = '1433';
                        break;
                    default: // MySQL
                        $port = '3306';
                        break;
                }
            }
            $data['port'] = $port;
        }
        return $data;
    }
}
