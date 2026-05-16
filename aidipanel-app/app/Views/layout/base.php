<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'AidiPanel') ?> — AidiPanel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: { DEFAULT: '#3C3489', light: '#534AB7', pale: '#EEEDFE' },
          }
        }
      }
    }
  </script>
  <style>
    [x-cloak] { display: none !important; }
    ::-webkit-scrollbar { width: 4px; height: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
    .sidebar-link { @apply flex items-center gap-2.5 px-3 py-2 text-sm text-gray-600 rounded-lg transition-all; }
    .sidebar-link:hover { @apply bg-gray-100 text-gray-900; }
    .sidebar-link.active { @apply bg-brand-pale text-brand font-medium; }
  </style>
</head>
<body class="bg-gray-50 h-full font-sans antialiased" x-data="{ mobileSidebar: false }">

<div class="flex h-screen overflow-hidden">

  <!-- Sidebar -->
  <aside class="w-56 min-w-56 bg-white border-r border-gray-200 flex flex-col overflow-hidden">

    <!-- Logo -->
    <div class="h-14 flex items-center gap-2.5 px-4 border-b border-gray-200 shrink-0">
      <div class="w-8 h-8 bg-brand rounded-lg flex items-center justify-center">
        <i class="ti ti-server text-white text-base"></i>
      </div>
      <span class="text-sm font-semibold text-gray-900">AidiPanel</span>
    </div>

    <!-- Nav -->
    <nav class="flex-1 px-3 py-3 overflow-y-auto">
      <?php
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $isActive = fn(string $path) => str_starts_with($uri, $path) ? 'active' : '';
      ?>

      <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-3 mb-1">Overview</p>
      <a href="/dashboard" class="sidebar-link <?= $uri === '/dashboard' || $uri === '/' ? 'active' : '' ?>">
        <i class="ti ti-layout-dashboard text-base"></i> Dashboard
      </a>
      <a href="/sites" class="sidebar-link <?= $isActive('/sites') ?>">
        <i class="ti ti-world text-base"></i> Sites
      </a>

      <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-3 mt-4 mb-1">Server</p>
      <a href="/cache" class="sidebar-link <?= $isActive('/cache') ?>">
        <i class="ti ti-bolt text-base"></i> FastCGI Cache
      </a>
      <a href="/php" class="sidebar-link <?= $isActive('/php') ?>">
        <i class="ti ti-brand-php text-base"></i> PHP-FPM
      </a>
      <a href="/services" class="sidebar-link <?= $isActive('/services') ?>">
        <i class="ti ti-activity text-base"></i> Services
      </a>

      <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-3 mt-4 mb-1">Management</p>
      <a href="/databases" class="sidebar-link <?= $isActive('/databases') ?>">
        <i class="ti ti-database text-base"></i> Databases
      </a>
      <a href="/ssl" class="sidebar-link <?= $isActive('/ssl') ?>">
        <i class="ti ti-lock text-base"></i> SSL / TLS
      </a>
      <a href="/users" class="sidebar-link <?= $isActive('/users') ?>">
        <i class="ti ti-users text-base"></i> Users
      </a>
      <a href="/logs" class="sidebar-link <?= $isActive('/logs') ?>">
        <i class="ti ti-file-text text-base"></i> Logs
      </a>
    </nav>

    <!-- Server info -->
    <div class="px-3 pb-3 shrink-0">
      <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
        <div class="overflow-hidden">
          <p class="text-xs font-medium text-gray-800 truncate"><?= e(gethostname() ?: 'server') ?></p>
          <p class="text-[10px] text-gray-400">AidiPanel v<?= PANEL_VERSION ?></p>
        </div>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <div class="flex-1 flex flex-col overflow-hidden">

    <!-- Topbar -->
    <header class="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-5 shrink-0">
      <h1 class="text-sm font-semibold text-gray-900"><?= e($pageTitle ?? 'Dashboard') ?></h1>
      <div class="flex items-center gap-2">
        <?php if (!empty($_flash_success ?? '')): ?>
          <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,4000)"
               class="text-xs bg-green-50 text-green-700 border border-green-200 px-3 py-1 rounded-full flex items-center gap-1.5">
            <i class="ti ti-circle-check"></i> <?= e($_flash_success) ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($_flash_error ?? '')): ?>
          <div x-data="{show:true}" x-show="show"
               class="text-xs bg-red-50 text-red-600 border border-red-200 px-3 py-1 rounded-full flex items-center gap-1.5">
            <i class="ti ti-alert-circle"></i> <?= e($_flash_error) ?>
          </div>
        <?php endif; ?>

        <div class="flex items-center gap-1.5 ml-2 pl-3 border-l border-gray-200">
          <div class="w-7 h-7 rounded-full bg-brand-pale flex items-center justify-center">
            <i class="ti ti-user text-brand text-xs"></i>
          </div>
          <span class="text-xs text-gray-700"><?= e($_user['username'] ?? 'admin') ?></span>
          <a href="/logout" class="ml-1 text-gray-400 hover:text-gray-700 transition-colors" title="Logout">
            <i class="ti ti-logout text-sm"></i>
          </a>
        </div>
      </div>
    </header>

    <!-- Content -->
    <main class="flex-1 overflow-y-auto p-5">
      <?= $_content ?? '' ?>
    </main>

  </div>
</div>

<!-- CSRF token for JS fetch -->
<meta name="csrf-token" content="<?= e($_csrf_token ?? '') ?>">

<script>
// Global fetch helper with CSRF
window.api = async (url, method = 'GET', body = null) => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const opts = {
    method,
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
  };
  if (body) {
    if (method === 'POST') {
      opts.body = JSON.stringify({ ...body, _csrf_token: csrf });
    }
  }
  const res = await fetch(url, opts);
  return res.json();
};
</script>

</body>
</html>
