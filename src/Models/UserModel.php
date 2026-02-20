<?php

namespace App\Models;

use App\Core\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['username', 'password', 'role', 'email', 'allowed_accounts', 'created_at', 'updated_at'];

    public function insert($data)
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        return parent::insert($data);
    }

    public function update($id, $data)
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            // If empty password provided during update, typically means "don't change"
            // But if it's passed as key, we might need to be careful.
            // For now, assuming controller filters it out if empty.
            if (isset($data['password']) && empty($data['password'])) {
                unset($data['password']);
            }
        }

        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return parent::update($id, $data);
    }
}
