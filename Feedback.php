<?php
namespace src;

use InvalidArgumentException;
use src\Entity;
use src\exceptions\InvalidException;

class Feedback extends Entity {
    protected string $name;
    protected string $status;
    protected string $phone;
    protected string $feedback;
    protected array $img;
    protected string $created_at;
    protected int $rating;
    protected string $agree;
    public string $tableName = 'review';

    public function getName(): string 
    {
        return $this->name ?? '';
    }

    public function getPhone(): string 
    {
        return $this->phone ?? '';
    }

    public function getFeedback(): string 
    {
        return $this->feedback ?? '';
    }

    public function loadFromForm(array $fields, array $files) : void {
        $fields['img'] = $files;
        $this->load($fields);
    }

    public function validate(): void {
        if(empty($this->name)){
            throw new InvalidArgumentException('Не передано имя');
        }
        if(empty($this->phone)){
            throw new InvalidArgumentException('Не передан телефон');
        }
        if(empty($this->feedback)){
            throw new InvalidArgumentException('Не передан текст отзыва');
        }
        if(!preg_match('/^[а-яА-Я\-]+\s[а-яА-Я\-]+(\s[а-яА-Я\-]+){0,1}$/u', $this->name)){
            throw new InvalidArgumentException('Неверный формат ФИО' );
        }
        if(!preg_match('/^[а-яА-Я\s\-]+$/u', $this->name)){
            throw new InvalidArgumentException('Неверный формат ФИО' );
        }
        if(!preg_match('/^\+7\(\d{3}\)\-\d{3}\-\d{2}\-\d{2}$/', $this->phone)){
            throw new InvalidArgumentException('Введите тел в формате +7(XXX)-XXX-XX-XX');
        }
        if(strlen($this->feedback) > 255){
            throw new InvalidArgumentException('Превышен лимит символов всего 255');
        }elseif(strlen($this->feedback) < 1
        ){
            throw new InvalidArgumentException('Отзыв должен быть не более 10 символов');
        }
        if(empty($this->img['tmp_name'])){
            throw new InvalidArgumentException('Файл не загружен');
        }
        $allowedExtenstion = ['jpg', 'png', 'gif'];
        $extenstion = pathinfo($this->img['name'], PATHINFO_EXTENSION);
        if(!in_array($extenstion, $allowedExtenstion)){
            throw new InvalidArgumentException("Загрузите файл с расширение 'jpg', 'png', 'gif'");
        }
        if($this->img['size'] > 5*1024*1024){
            throw new InvalidArgumentException('Слишком большой файл, более 5мб');
        }
        if(empty($this->agree)){
            throw new InvalidArgumentException('Нужно согласиться на обработку данных');
        }
    }     

    public function save(): bool {
        $pathFile = 'uploads/' . $this->img['name'];
        if(!move_uploaded_file($this->img['tmp_name'], $pathFile)){
            throw new InvalidArgumentException('Ошибка при загрузке файла');
        }
        $fields = ['name' => $this->name, 'phone' => $this->phone, 'feedback' => $this->feedback,
        'img' => $pathFile
        ];
        return $this->insert($fields);
    }
}
