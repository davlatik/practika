<?php
namespace src\services;
class Request 
{
    public $isPost = false;
    public $isGet = false;
    public function __construct(){
        if($_SERVER['REQUEST_METHOD'] == 'GET') {
            $this->isGet = true;
        }elseif($_SERVER['REQUEST_METHOD'] == 'POST'){
            $this->isPost = true;
        }
    }
    public function clearParam($param): string
    {
        return trim(strip_tags($param));
    }
    public function clearArray($array): array
    {
        $result = [];
        foreach($array as $key => $value){
            if(gettype($value) == 'array'){
                $result[$key] = $this->clearArray($value);
                continue;
            }
            $result[$key] = $this->clearParam($value);
        }
        return $result;
    }
    public function post($param = null): array|string
    {
        if($param){
            return $this->clearParam($_POST[$param]);
        }else{
            return $this->clearArray($_POST);
        }
    }
    public function hostGet($param = null): array|string
    {
        if($param){
            return $this->clearParam($_GET[$param]);
        }else{
             return $this->clearArray($_GET);
        }
    }
}
?>