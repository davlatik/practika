<?php

namespace src\services;

use mysqli_result;

class Db extends \mysqli
{
    public function __construct($config)
    {
        try {
            // Отключаем встроенные отчеты об ошибках mysqli, чтобы перехватить их через try-catch
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            parent::__construct($config['hostname'], $config['username'], $config['password'], $config['database']);
        } catch (\mysqli_sql_exception $e) {
            // Используем стандартный \Exception вместо отсутствующего класса
            throw new \Exception('Ошибка при подключении к базе данных: ' . $e->getMessage());
        }
    }
    
    public function querySql(string $sql, array $params = []): array|bool
    {
        $result = parent::query($sql);
        if (gettype($result) == 'boolean') return $result;
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
