<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        return view('auth/login');
    }

    public function attemptLogin()
    {
        $rules = [
            'username' => 'required|min_length[3]',
            'password' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $userModel = new UserModel();
        $user = $userModel->where('username', $username)->first();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $sessionData = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'allowed_accounts' => $user['allowed_accounts'],
                    'isLoggedIn' => true,
                ];

                session()->set($sessionData);
                return redirect()->to('/');
            }
        }

        return redirect()->back()->withInput()->with('error', 'Invalid login credentials');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/');
    }

    public function changePassword()
    {
        return view('auth/change_password');
    }

    public function attemptChangePassword()
    {
        $rules = [
            'password' => 'required',
            'confpassword' => 'required|matches[password]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userModel = new UserModel();
        $userId = session()->get('id');

        $data = [
            'password' => $this->request->getPost('password')
        ];

        // UserModel handles hashing in beforeUpdate callback
        if ($userModel->update($userId, $data)) {
            return redirect()->to('/')->with('message', 'Password changed successfully');
        } else {
            return redirect()->back()->withInput()->with('errors', $userModel->errors());
        }
    }
}
