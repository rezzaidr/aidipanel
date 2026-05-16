<?php
declare(strict_types=1);
namespace Controllers;

class SiteController extends BaseController
{
    public function index(array $params = []): void
    {
        $sites = $this->db->rows('SELECT * FROM sites ORDER BY created_at DESC');
        $this->view('sites/index', compact('sites'));
    }

    public function showAdd(array $params = []): void
    {
        $this->view('sites/add', [
            'phpVersions' => ['8.1', '8.2', '8.3'],
            'siteTypes'   => [
                'wordpress' => 'WordPress',
                'php'       => 'PHP / Generic',
                'laravel'   => 'Laravel',
                'static'    => 'Static HTML',
                'proxy'     => 'Reverse Proxy',
            ],
        ]);
    }

    public function add(array $params = []): void
    {
        $domain    = strtolower(trim((string) $this->request->post('domain', '')));
        $type      = (string) $this->request->post('type', 'php');
        $phpVer    = (string) $this->request->post('php_version', '8.3');
        $proxyPass = (string) $this->request->post('proxy_pass', 'http://127.0.0.1:3000');

        // Validate
        if (!is_valid_domain($domain)) {
            $this->error('Invalid domain name.');
        }
        if (!in_array($type, ['wordpress', 'php', 'laravel', 'static', 'proxy'], true)) {
            $this->error('Invalid site type.');
        }
        if (!in_array($phpVer, ['8.1', '8.2', '8.3'], true)) {
            $this->error('Invalid PHP version.');
        }
        if ($this->db->row('SELECT id FROM sites WHERE domain = ?', [$domain])) {
            $this->error("Site already exists: {$domain}");
        }

        // Build CLI args
        $args = ['--domain', $domain, '--type', $type, '--php', $phpVer];
        if ($type === 'proxy') {
            $args[] = '--proxy-pass';
            $args[] = $proxyPass;
        }

        $result = run_cli('site:add', $args);

        if (!$result['success']) {
            $this->error('Failed to create site: ' . $result['output']);
        }

        // Persist to panel DB
        $webroot = "/var/www/{$domain}/htdocs";
        if ($type === 'laravel') {
            $webroot .= '/public';
        }

        $this->db->run(
            'INSERT INTO sites (domain, type, php_version, webroot, ssl_type, cache_enabled) VALUES (?, ?, ?, ?, ?, ?)',
            [$domain, $type, $phpVer, $webroot, 'self-signed', $type !== 'static' ? 1 : 0]
        );

        \Core\DB::log('site:add', "Added site: {$domain} ({$type}, PHP {$phpVer})");
        $this->success("Site {$domain} created successfully.", "/sites/{$domain}");
    }

    public function detail(array $params = []): void
    {
        $domain = $params['domain'] ?? '';
        $site   = $this->db->row('SELECT * FROM sites WHERE domain = ?', [$domain]);
        if (!$site) {
            abort(404, "Site not found: {$domain}");
        }

        $nginxConf = '';
        $confFile  = "/etc/nginx/sites-available/{$domain}.conf";
        if (file_exists($confFile) && is_readable($confFile)) {
            $nginxConf = file_get_contents($confFile);
        }

        // SSL expiry
        $sslExpiry = null;
        $lePath    = "/etc/letsencrypt/live/{$domain}/fullchain.pem";
        if (file_exists($lePath)) {
            $certInfo  = openssl_x509_parse((string) file_get_contents($lePath));
            $sslExpiry = $certInfo ? date('Y-m-d', $certInfo['validTo_time_t']) : null;
            $site['ssl_type'] = "Let's Encrypt";
        }

        $logs = $this->db->rows(
            'SELECT * FROM activity_log WHERE detail LIKE ? ORDER BY created_at DESC LIMIT 20',
            ["%{$domain}%"]
        );

        $this->view('sites/detail', compact('site', 'nginxConf', 'sslExpiry', 'logs'));
    }

    public function delete(array $params = []): void
    {
        $domain = $params['domain'] ?? '';
        $site   = $this->db->row('SELECT id FROM sites WHERE domain = ?', [$domain]);
        if (!$site) {
            $this->error("Site not found: {$domain}");
        }

        $result = run_cli('site:delete', ['--domain', $domain]);
        if (!$result['success']) {
            $this->error('Failed to delete site: ' . $result['output']);
        }

        $this->db->run('DELETE FROM sites WHERE domain = ?', [$domain]);
        \Core\DB::log('site:delete', "Deleted site: {$domain}");
        $this->success("Site {$domain} deleted.", '/sites');
    }

    public function changePhp(array $params = []): void
    {
        $domain = $params['domain'] ?? '';
        $phpVer = (string) $this->request->post('php_version', '8.3');

        if (!in_array($phpVer, ['8.1', '8.2', '8.3'], true)) {
            $this->error('Invalid PHP version.');
        }

        $result = run_cli('php:version', ['--domain', $domain, '--set', $phpVer]);
        if (!$result['success']) {
            $this->error('Failed to change PHP version: ' . $result['output']);
        }

        $this->db->run('UPDATE sites SET php_version = ? WHERE domain = ?', [$phpVer, $domain]);
        \Core\DB::log('php:version', "Changed {$domain} to PHP {$phpVer}");
        $this->success("PHP version changed to {$phpVer} for {$domain}.", "/sites/{$domain}");
    }

    public function nginxEditor(array $params = []): void
    {
        $domain   = $params['domain'] ?? '';
        $site     = $this->db->row('SELECT * FROM sites WHERE domain = ?', [$domain]);
        if (!$site) abort(404);

        $confFile  = "/etc/nginx/sites-available/{$domain}.conf";
        $nginxConf = file_exists($confFile) ? (string) file_get_contents($confFile) : '';

        $this->view('sites/nginx-editor', compact('site', 'nginxConf'));
    }

    public function saveNginx(array $params = []): void
    {
        $domain   = $params['domain'] ?? '';
        $content  = (string) $this->request->post('nginx_conf', '');

        if (empty($content)) {
            $this->error('Nginx config cannot be empty.');
        }

        $confFile = "/etc/nginx/sites-available/{$domain}.conf";
        if (!file_exists($confFile)) {
            $this->error("Nginx config not found for: {$domain}");
        }

        // Backup before saving
        copy($confFile, $confFile . '.bak.' . time());

        // Write new config
        file_put_contents($confFile, $content);

        // Test nginx config
        exec('nginx -t 2>&1', $output, $code);
        if ($code !== 0) {
            // Restore backup
            copy($confFile . '.bak.' . time(), $confFile);
            $this->error('Nginx config test failed: ' . implode("\n", $output));
        }

        exec('systemctl reload nginx 2>&1');
        \Core\DB::log('nginx:save', "Saved Nginx config for {$domain}");
        $this->success('Nginx configuration saved and reloaded.', "/sites/{$domain}");
    }
}
