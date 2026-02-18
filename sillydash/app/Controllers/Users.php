<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\AccountModel;

class Users extends BaseController
{
    public function index()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('/');
        }

        $userModel = new UserModel();

        $data = [
            'users' => $userModel->findAll(),
        ];

        return view('users/index', $data);
    }

    public function create()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('/');
        }

        $accountModel = new AccountModel();
        return view('users/create', [
            'accounts' => $accountModel->getTopLevelAccounts()
        ]);
    }

    public function store()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('/');
        }

        $userModel = new UserModel();

        $data = [
            'username' => trim(strip_tags($this->request->getPost('username'))),
            'email' => trim(strip_tags($this->request->getPost('email'))),
            'role' => in_array($this->request->getPost('role'), ['admin', 'user'], true)
                ? $this->request->getPost('role') : 'user',
            'password' => $this->request->getPost('password'),
        ];

        // Allowed accounts
        $allowed = $this->request->getPost('allowed_accounts');
        if (is_array($allowed)) {
            $data['allowed_accounts'] = implode(',', $allowed);
        } else {
            $data['allowed_accounts'] = '';
        }

        if ($userModel->save($data)) {
            return redirect()->to('users')->with('message', 'User created successfully.');
        }

        // On failure, reload accounts to show the form again with errors
        $accountModel = new AccountModel();
        return view('users/create', [
            'errors' => $userModel->errors(),
            'accounts' => $accountModel->getTopLevelAccounts()
        ]);
    }

    public function edit($id)
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('/');
        }

        $userModel = new UserModel();
        $user = $userModel->find($id);

        if (!$user) {
            return redirect()->to('users')->with('error', 'User not found.');
        }

        $accountModel = new AccountModel();
        return view('users/edit', [
            'user' => $user,
            'accounts' => $accountModel->getTopLevelAccounts()
        ]);
    }

    public function update($id)
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('/');
        }

        $userModel = new UserModel();
        $user = $userModel->find($id);

        if (!$user) {
            return redirect()->to('users')->with('error', 'User not found.');
        }

        $data = [
            'username' => trim(strip_tags($this->request->getPost('username'))),
            'email' => trim(strip_tags($this->request->getPost('email'))),
            'role' => in_array($this->request->getPost('role'), ['admin', 'user'], true)
                ? $this->request->getPost('role') : 'user',
        ];

        // Allowed accounts
        $allowed = $this->request->getPost('allowed_accounts');
        if (is_array($allowed)) {
            $data['allowed_accounts'] = implode(',', $allowed);
        } else {
            $data['allowed_accounts'] = '';
        }

        // Override unique rules to exclude the current user being edited
        $userModel->setValidationRule('username', "required|min_length[3]|max_length[100]|is_unique[users.username,id,{$id}]");
        $userModel->setValidationRule('email', "required|valid_email|is_unique[users.email,id,{$id}]");

        // Only update password if provided
        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $data['password'] = $password;
        }

        // Skip password validation if not changing it
        if (empty($password)) {
            $userModel->setValidationRule('password', 'permit_empty|min_length[0]');
        }

        if ($userModel->update($id, $data)) {
            return redirect()->to('users')->with('message', 'User updated successfully.');
        }

        // On failure, reload accounts
        $accountModel = new AccountModel();
        return view('users/edit', [
            'user' => $data + ['id' => $id], // preserve submitted data
            'errors' => $userModel->errors(),
            'accounts' => $accountModel->getTopLevelAccounts()
        ]);
    }
}
