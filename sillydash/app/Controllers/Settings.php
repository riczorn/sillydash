<?php

namespace App\Controllers;

use App\Models\Setup as SetupModel;

class Settings extends BaseController
{
    public function index()
    {
        // Admin-only access
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('/');
        }

        // Load current config
        $config = [];
        $configFile = ROOTPATH . 'config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
        }

        return view('settings/index', ['config' => $config]);
    }

    public function save()
    {
        // Admin-only access
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return redirect()->to('/');
        }

        // Load existing config (preserve DB credentials and anything not in the form)
        $configFile = ROOTPATH . 'config.php';
        $config = file_exists($configFile) ? require $configFile : [];

        // Sanitize and merge settings
        $config['list_folder'] = trim(strip_tags($this->request->getPost('list_folder') ?? './files'));
        $config['cron_token'] = trim(strip_tags($this->request->getPost('cron_token') ?? ''));

        // SMTP settings
        $smtpHost = trim(strip_tags($this->request->getPost('smtp_host') ?? ''));
        if ($smtpHost) {
            $config['smtp'] = [
                'host' => $smtpHost,
                'port' => max(1, min(65535, (int) ($this->request->getPost('smtp_port') ?? 587))),
                'security' => in_array($this->request->getPost('smtp_security'), ['tls', 'ssl', 'none'], true)
                    ? $this->request->getPost('smtp_security') : 'tls',
                'user' => trim(strip_tags($this->request->getPost('smtp_user') ?? '')),
                'pass' => $this->request->getPost('smtp_pass') ?? '',
            ];
        } else {
            // Clear SMTP if host is empty
            unset($config['smtp']);
        }

        // Write config
        if (SetupModel::writeConfigArray($config)) {
            return redirect()->to('settings')->with('message', 'Settings saved successfully.');
        }

        return view('setup/manual_config', [
            'configContent' => "<?php\n\nreturn " . var_export($config, true) . ";\n",
            'message' => 'Could not write config.php (check file permissions). Please save the following content manually to: ' . ROOTPATH . 'config.php',
        ]);
    }

    public function testEmail()
    {
        if (!session()->get('isLoggedIn') || session()->get('role') !== 'admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Not authenticated.']);
        }

        // Get user email
        $userModel = new \App\Models\UserModel();
        $user = $userModel->find(session()->get('id'));
        $toEmail = $user['email'] ?? '';

        if (empty($toEmail)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Your account has no email address.']);
        }

        // Use values from the form (POST) to allow testing unsaved changes
        $smtp = [
            'host' => trim($this->request->getPost('smtp_host')),
            'port' => (int) $this->request->getPost('smtp_port'),
            'security' => $this->request->getPost('smtp_security'),
            'user' => trim($this->request->getPost('smtp_user')),
            'pass' => $this->request->getPost('smtp_pass'),
        ];

        if (empty($smtp['host'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'SMTP host is required.']);
        }

        try {
            $email = \Config\Services::email();
            $email->initialize([
                'protocol' => 'smtp',
                'SMTPHost' => $smtp['host'],
                'SMTPPort' => $smtp['port'] ?: 587,
                'SMTPCrypto' => ($smtp['security'] === 'none') ? '' : ($smtp['security'] ?: 'tls'),
                'SMTPUser' => $smtp['user'],
                'SMTPPass' => $smtp['pass'],
                'mailType' => 'html',
                'newline' => "\r\n",
                'CRLF' => "\r\n",
            ]);

            $email->setFrom($smtp['user'] ?? 'noreply@example.com', 'Silly Dashboard');
            $email->setTo($toEmail);
            $email->setSubject('Silly Dashboard - SMTP Test');
            $email->setMessage('<h2>SMTP Test Successful</h2><p>This is a test email from Silly Dashboard sent at ' . date('Y-m-d H:i:s') . '.</p><p>Your SMTP configuration is working correctly.</p>');

            if ($email->send()) {
                return $this->response->setJSON(['success' => true, 'message' => "Test email sent successfully to {$toEmail}"]);
            } else {
                $debugMsg = $email->printDebugger(['headers', 'subject']);
                return $this->response->setJSON(['success' => false, 'message' => 'Failed to send: ' . strip_tags($debugMsg)]);
            }
        } catch (\Throwable $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
