<?php
namespace src;

use src\exceptions\InvalidArgumentException;
use src\Entity;

class User extends Entity {
    protected string $tableName = 'users';

    public ?string $login = '';
    public ?string $email = '';
    public ?string $password = '';
    public ?string $role = ''; 
    public ?string $name = '';
    public ?string $phone = 'user';
    public ?string $token = '';

    public bool $isGuest = true;
    public bool $isAdmin = false;

    public function getLogin(): ?string 
    {
        return $this->login ?? '';
    }

    public function getName(): ?string 
    {
        return $this->name ?? '';
    }

    public function getEmail(): ?string 
    {
        return $this->email ?? '';
    }

    public function getPhone(): ?string 
    {
        return $this->phone ?? '';
    }

    public function loadFromForm(array $fields): void {
        $this->load($fields);
    }

    public function isAdmin(): bool {
        return ($this->role === 'admin');
    }

    public function validate(): void {
        if (empty($this->login)) throw new InvalidArgumentException('Не передан логин');
        if (empty($this->email)) throw new InvalidArgumentException('Не передан email');
        if (empty($this->name)) throw new InvalidArgumentException('Не передан ФИО');
        if (empty($this->password)) throw new InvalidArgumentException('Не передан пароль');

        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $this->login)) {
            throw new InvalidArgumentException('Логин должен содержать от 3 до 20 символов латиницы или цифр');
        }
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Неверный формат email');
        }
        if (mb_strlen($this->password) < 6) {
            throw new InvalidArgumentException('Пароль должен быть не менее 6 символов');
        }
    }

    public function save(): bool {
        $name = addslashes($this->name ?? '');
        $login = addslashes($this->login ?? '');
        $email = addslashes($this->email ?? '');
        $phone = addslashes($this->phone ?? '');
        $password = addslashes($this->password ?? '');
        $role = !empty($this->role) ? addslashes($this->role) : 'user';

        $fields = [
            'name' => $name,
            'login' => $login,
            'email' => $email,
            'password' => $password,
            'phone' => $phone,
            'role' => $role
        ];

        $result = $this->insert($fields);
        if ($result){
            $this->isGuest = false;
            if ($this->role === 'admin') {
                $this->isAdmin = true;
            }
        }
        return $result;
    }

    public function validateLogin(): void {
        if(empty($this->login)) {
            throw new InvalidArgumentException('Логин не может быть пустым');
        }
        if(empty($this->password)) {
            throw new InvalidArgumentException('Пароль не может быть пустым');
        }
    }

    public function login(): void {
        // 1. Ищем пользователя по логину в базе данных
        $searchResult = $this->findByColumn('login', addslashes($this->login));
        
        if (empty($searchResult) || !is_array($searchResult)) {
            throw new InvalidArgumentException('Пользователь с таким логином не найден');
        }

        // 2. Гарантированно приводим к одномерному массиву строки пользователя
        if (isset($searchResult[0]) && is_array($searchResult[0])) {
            $userData = $searchResult[0];
        } else {
            $userData = $searchResult;
        }

        // 3. Динамически определяем ключ пароля в массиве БД
        $passwordKey = null;
        foreach (['password', 'pass', 'PASSWORD', 'user_password'] as $key) {
            if (array_key_exists($key, $userData)) {
                $passwordKey = $key;
                break;
            }
        }

        if ($passwordKey === null) {
            throw new InvalidArgumentException('Ошибка системы. Не найдено поле пароля в таблице.');
        }

        // 4. Сверка паролей: поддерживает и чистый текст, и захешированный через password_hash()
        $isValidPassword = false;
        if ($userData[$passwordKey] === $this->password) {
            $isValidPassword = true; // Совпадение по чистому тексту
        } elseif (password_verify($this->password, $userData[$passwordKey])) {
            $isValidPassword = true; // Совпадение по хэшу
        }

        if (!$isValidPassword) {
            throw new InvalidArgumentException('Неверный логин или пароль');
        }

        // 5. Загружаем данные в текущий объект
        $this->load($userData);

        // 6. Генерация авторизационного токена куки
        $generatedToken = sha1(random_bytes(100));
        $userId = $this->id ?? $userData['id'] ?? null;
        
        $dbConnection = $this->db ?? $this->dbConnection ?? null;
        if ($userId && $dbConnection) {
            $safeToken = addslashes($generatedToken);
            $sql = "UPDATE `{$this->tableName}` SET token = '{$safeToken}' WHERE id = " . (int)$userId;
            $dbConnection->query($sql);
        }

        $cookieValue = $userId . ':' . $generatedToken;
        setcookie('token', $cookieValue, 0, '/', '', false, true);
    }

    public function logout(){
        if(isset($_COOKIE['token'])){
            setcookie('token', '', -1, '/', '', false, true);
        }
    }

    public function identity(): ?array {
        $token = $_COOKIE['token'] ?? '';
        if (empty($token) || !str_contains($token, ':')) {
            return null;
        }

        [$userId, $authToken] = explode(':', $token, 2);
        $searchResult = $this->getById((int)$userId);
        
        if ($searchResult === null || empty($searchResult)) {
            return null;
        }

        if (isset($searchResult[0]) && is_array($searchResult[0])) {
            $userRow = $searchResult[0];
        } else {
            $userRow = $searchResult;
        }

        $tokenKey = null;
        foreach (['token', 'TOKEN'] as $key) {
            if (array_key_exists($key, $userRow)) {
                $tokenKey = $key;
                break;
            }
        }

        if ($tokenKey === null || $userRow[$tokenKey] !== $authToken) {
            return null;
        }
        
        // Подгружаем данные распознанного пользователя в свойства объекта
        $this->load($userRow);
        $this->isGuest = false;
        $this->isAdmin = $this->isAdmin();

        return $userRow;
    }
    public function refreshAuthToken(): string {
        return sha1(random_bytes(100));
    }

    public function createTokenCookies(): void {      
    }
}
