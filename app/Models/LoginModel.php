<?php

namespace App\Models;

use CodeIgniter\HTTP\Request;
use CodeIgniter\Model;
use Config\Services;

class LoginModel extends Model
{
    protected $table         = 'login';
    protected $allowedFields = [
        'id', 'name', 'email', 'phone', 'password', 'otp', 'lang', 'email_verified_at',
    ];
    protected $primaryKey = 'id';
    protected $returnType = 'App\Entities\Login';
    protected $useTimestamps = true;

    public function register($data, $thenLogin = true, $autoVerified = false)
    {
        $data = array_intersect_key($data, array_flip(
            ['name', 'email', 'phone', 'password']
        ));
        $data['lang'] = Services::request()->getLocale();
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        if ($autoVerified) {
            $data['email_verified_at'] = date('Y-m-d H:i:s');
        }
        if ($this->save($data)) {
            if ($thenLogin) {
                Services::session()->set('login', $this->insertID);
            }
            return $this->insertID;
        }
    }
}
