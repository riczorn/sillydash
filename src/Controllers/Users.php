<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;
use App\Models\AccountModel;

class Users extends Controller
{
    public function index()
    {
        if (!$this->getSession('isLoggedIn')) {
            $this->redirect(site_url('login'));
        }

        // Only admin? Original code might allow users to see their own profile?
        // Let's assume admin for listing.
        // Actually, the original code checks if admin or self?
        // Let's check original content from read.

        if ($this->getSession('role') === 'admin') {
            $userModel = new UserModel();
            $users = $userModel->findAll();
            $this->view('users/index', ['users' => $users]);
        } else {
            // Redirect non-admin to their own edit page or dashboard
            $this->redirect(site_url('/'));
        }
    }

    public function create()
    {
        if ($this->getSession('role') !== 'admin') {
            $this->redirect(site_url('/'));
        }

        $accountModel = new AccountModel();
        // We need top level accounts for the dropdown
        $accounts = $accountModel->getTopLevelAccounts();

        $this->view('users/create', ['accounts' => $accounts]);
    }

    public function store()
    {
        if ($this->getSession('role') !== 'admin') {
            $this->redirect(site_url('/'));
        }

        $username = $this->getPost('username');
        $email = $this->getPost('email');
        $password = $this->getPost('password');
        $role = $this->getPost('role');
        $allowed = $this->getPost('allowed_accounts'); // Array from multiple select?

        if (is_array($allowed)) {
            $allowed = implode(',', $allowed); // specific domains
        } else {
            $allowed = ''; // All if admin, or logic handled elsewhere
        }

        // Validation
        if (strlen($username) < 3 || empty($password)) {
            $this->setSession('error', 'Validation failed');
            $this->redirect(site_url('users/create'));
        }

        $userModel = new UserModel();
        $userModel->insert([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'allowed_accounts' => $allowed
        ]);

        $this->setSession('success', 'User created');
        $this->redirect(site_url('users'));
    }

    public function edit($id)
    {
        if (!$this->getSession('isLoggedIn')) {
            $this->redirect(site_url('login'));
        }

        // Allow admin or self
        if ($this->getSession('role') !== 'admin' && $this->getSession('id') != $id) {
            $this->redirect(site_url('/'));
        }

        $userModel = new UserModel();
        $user = $userModel->find($id);

        $accountModel = new AccountModel();
        $accounts = $accountModel->getTopLevelAccounts();

        $this->view('users/edit', ['user' => $user, 'accounts' => $accounts]);
    }

    public function update($id)
    {
        if (!$this->getSession('isLoggedIn')) {
            $this->redirect(site_url('login'));
        }

        if ($this->getSession('role') !== 'admin' && $this->getSession('id') != $id) {
            $this->redirect(site_url('/'));
        }

        $data = [
            'username' => $this->getPost('username'),
            'email' => $this->getPost('email'),
            'role' => $this->getPost('role'),
        ];

        $password = $this->getPost('password');
        if (!empty($password)) {
            $data['password'] = $password;
        }

        $allowed = $this->getPost('allowed_accounts');
        if (is_array($allowed)) {
            $data['allowed_accounts'] = implode(',', $allowed);
        } else {
            // If not present (e.g. not admin editing), keep old?
            // Or if empty array?
            // Logic: If admin, update allowed_accounts. If user, don't change role or allowed_accounts.
        }

        if ($this->getSession('role') !== 'admin') {
            // Non-admin can only change own password/email/username? 
            // Usually just password/email.
            unset($data['role']);
            unset($data['allowed_accounts']);
        } else {
            // Admin updating
            if ($allowed === null)
                $data['allowed_accounts'] = '';
        }

        $userModel = new UserModel();
        $userModel->update($id, $data);

        $this->setSession('success', 'User updated');
        if ($this->getSession('role') === 'admin') {
            $this->redirect(site_url('users'));
        } else {
            $this->redirect(site_url('/'));
        }
    }
}
