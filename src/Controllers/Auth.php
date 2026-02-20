<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;

class Auth extends Controller
{
    public function attemptLogin()
    {
        $username = $this->getPost('username');
        $password = $this->getPost('password');

        $userModel = new UserModel();
        $users = $userModel->where('username', $username);
        $user = $users[0] ?? null;

        if ($user && password_verify($password, $user['password'])) {
            $this->setSession('isLoggedIn', true);
            $this->setSession('id', $user['id']);
            $this->setSession('username', $user['username']);
            $this->setSession('role', $user['role']);
            $this->setSession('allowed_accounts', $user['allowed_accounts']);

            $this->redirect(site_url('/'));
        } else {
            $this->setSession('error', 'Invalid login credentials');
            $this->redirect(site_url('/'));
        }
    }

    public function logout()
    {
        $this->destroySession();
        $this->redirect(site_url('/'));
    }

    public function changePassword()
    {
        if (!$this->getSession('isLoggedIn')) {
            $this->redirect(site_url('/'));
        }

        $this->view('auth/change_password');
    }

    public function attemptChangePassword()
    {
        if (!$this->getSession('isLoggedIn')) {
            $this->redirect(site_url('/'));
        }

        $oldPassword = $this->getPost('old_password');
        $newPassword = $this->getPost('new_password');
        $confirmPassword = $this->getPost('confirm_password');

        if ($newPassword !== $confirmPassword) {
            $this->setSession('error', 'New passwords do not match');
            $this->redirect(site_url('change-password'));
        }

        $userModel = new UserModel();
        $user = $userModel->find($this->getSession('id'));

        if (!$user || !password_verify($oldPassword, $user['password'])) {
            $this->setSession('error', 'Incorrect old password');
            $this->redirect(site_url('change-password'));
        }

        $userModel->update($this->getSession('id'), ['password' => $newPassword]);

        $this->setSession('success', 'Password changed successfully');
        $this->redirect(site_url('/'));
    }
}
