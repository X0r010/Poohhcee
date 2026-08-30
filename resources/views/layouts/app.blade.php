<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>@yield('title', 'Dashboard') — Poohhcee</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    boxShadow: {
                        '2xs': '0 1px 2px 0 rgba(0, 0, 0, 0.04)',
                        'xs': '0 1px 3px 0 rgba(0, 0, 0, 0.08), 0 1px 2px -1px rgba(0, 0, 0, 0.08)',
                    }
                },
            },
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=2">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <style>
    /* 1. Disable iOS Safari automatic text size inflation */
    html, body {
        -webkit-text-size-adjust: 100%;
        text-size-adjust: 100%;
    }

    body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
    [x-cloak] { display: none !important; }
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-thumb { background: #e4e4e7; border-radius: 3px; }

    /* 2. Baseline styling (12px everywhere) */
    input, select, textarea {
        font-size: 12px;
    }

    /* 3. Non-destructive iOS Zoom Fix: 
        Temporarily switch font-size to 16px ONLY on focus on mobile screens. 
        Zero width, margin, or transform changes—your columns stay untouched. */
    @media screen and (max-width: 768px) {
        input:not([type="checkbox"]):not([type="radio"]):focus, 
        select:focus, 
        textarea:focus {
            font-size: 16px !important;
        }
    }

    /* Status & Payment Badges */
    .badge { display: inline-flex; align-items: center; gap: 0.25rem; border-radius: 0.375rem; padding: 0.125rem 0.5rem; font-size: 11px; font-weight: 500; border-width: 1px; }
    .badge-ready { background-color: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .badge-missing-shirt { background-color: #fffbeb; color: #b45309; border-color: #fde68a; }
    .badge-missing-film { background-color: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
    .badge-missing-both { background-color: #fef2f2; color: #b91c1c; border-color: #fecaca; }
    .badge-unknown { background-color: #f4f4f5; color: #52525b; border-color: #e4e4e7; }

    .status-printed { background-color: #e0e7ff; color: #3730a3; border-color: #c7d2fe; }
    .status-pending { background-color: #f4f4f5; color: #52525b; border-color: #e4e4e7; }
    .status-packaging { background-color: #fffbeb; color: #b45309; border-color: #fde68a; }
    .status-delivering { background-color: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
    .status-delivered { background-color: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .status-cancelled { background-color: #fef2f2; color: #b91c1c; border-color: #fecaca; text-decoration: line-through; }

    .payment-paid { background-color: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .payment-partial { background-color: #fffbeb; color: #b45309; border-color: #fde68a; }
    .payment-unpaid { background-color: #fef2f2; color: #b91c1c; border-color: #fecaca; }
    </style>

    @stack('styles')
</head>
<body class="bg-[#fafafa] text-zinc-900 antialiased text-xs" x-data="{ sidebarOpen: false }">

    <div class="flex min-h-screen">
        {{-- ── Sidebar ─────────────────────────────────────────── --}}
        <aside class="w-56 shrink-0 bg-white border-r border-zinc-200/90 text-zinc-700 flex flex-col fixed inset-y-0 left-0 z-30
                       -translate-x-full lg:translate-x-0 transition-transform"
               :class="sidebarOpen && '!translate-x-0'">

            <!-- Brand Header -->
            <div class="h-14 flex items-center gap-2.5 px-4 border-b border-zinc-100">
                <div class="w-6 h-6 rounded-md bg-black flex items-center justify-center font-bold text-white text-xs shadow-2xs">P</div>
                <span class="font-bold text-zinc-900 tracking-tight text-sm">Poohhcee</span>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 overflow-y-auto px-2.5 py-4 space-y-4">
                <div>
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-zinc-100 text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50' }}">
                        <svg class="w-4 h-4 shrink-0 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        <span>Dashboard</span>
                    </a>
                </div>

                <div class="space-y-0.5">
                    <p class="px-2.5 text-[10px] font-bold tracking-wider text-zinc-400 uppercase mb-1">ORDERS</p>
                    <a href="{{ route('orders.index') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('orders.index') ? 'bg-zinc-100 text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50' }}">
                        <svg class="w-4 h-4 shrink-0 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span>All Orders</span>
                    </a>
                    <a href="{{ route('orders.pipeline') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('orders.pipeline') ? 'bg-zinc-100 text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50' }}">
                        <svg class="w-4 h-4 shrink-0 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Pipeline</span>
                    </a>
                    <a href="{{ route('orders.create') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('orders.create') ? 'bg-zinc-100 text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50' }}">
                        <svg class="w-4 h-4 shrink-0 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"/></svg>
                        <span>New Order</span>
                    </a>
                    <a href="{{ route('orders.buylist') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('orders.buylist') ? 'bg-zinc-100 text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50' }}">
                        <svg class="w-4 h-4 shrink-0 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span>Buy List</span>
                    </a>
                </div>

                <div class="space-y-0.5">
                    <p class="px-2.5 text-[10px] font-bold tracking-wider text-zinc-400 uppercase mb-1">INVENTORY</p>
                    <a href="{{ route('inventory.shirts') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('inventory.shirts') ? 'bg-zinc-100 text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50' }}">
                        <svg class="w-4 h-4 shrink-0 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                        <span>Shirts</span>
                    </a>
                    <a href="{{ route('inventory.films') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('inventory.films') ? 'bg-zinc-100 text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50' }}">
                        <svg class="w-4 h-4 shrink-0 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>
                        <span>DTF Films</span>
                    </a>
                </div>

                <div class="space-y-0.5">
                    <p class="px-2.5 text-[10px] font-bold tracking-wider text-zinc-400 uppercase mb-1">BUSINESS</p>
                    <a href="{{ route('finance.index') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('finance.index') ? 'bg-zinc-100 text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50' }}">
                        <svg class="w-4 h-4 shrink-0 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Finance</span>
                    </a>
                    <a href="{{ route('collections.index') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('collections.index') ? 'bg-zinc-100 text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50' }}">
                        <svg class="w-4 h-4 shrink-0 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                        <span>Collections</span>
                    </a>
                    <a href="{{ route('settings.index') }}" class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('settings.index') ? 'bg-zinc-100 text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50' }}">
                        <svg class="w-4 h-4 shrink-0 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Settings</span>
                    </a>
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="border-t border-zinc-100 p-3 space-y-2">
                <div class="flex items-center justify-between px-1 text-[11px]">
                    <span class="font-medium text-zinc-400">Studio v1.0</span>
                    <button onclick="document.documentElement.classList.toggle('dark')" class="flex items-center gap-1 text-zinc-500 hover:text-zinc-800 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <span>Dark</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full text-xs text-zinc-700 hover:text-zinc-900 rounded-lg border border-zinc-200/90 py-1.5 hover:bg-zinc-50 transition-colors font-medium shadow-2xs">
                        Sign Out
                    </button>
                </form>
            </div>
        </aside>

        <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/20 z-20 lg:hidden backdrop-blur-xs"></div>

        {{-- ── Main Content Area ──────────────────────────────── --}}
        <div class="flex-1 lg:ml-56 min-w-0">
            <header class="h-14 sticky top-0 z-10 bg-[#fafafa]/80 backdrop-blur-md flex items-center justify-between px-6 lg:px-8 border-b border-zinc-200/40">
                <div class="flex items-center gap-3">
                    <button class="lg:hidden text-zinc-500 hover:text-zinc-900" @click="sidebarOpen = true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h1 class="text-sm font-bold text-zinc-900 tracking-tight">@yield('title', 'Dashboard')</h1>
                </div>

                <div class="flex items-center gap-3">
                    @yield('header-actions')

                    <!-- User Profile Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="w-8 h-8 rounded-full bg-zinc-900 text-white text-xs font-semibold flex items-center justify-center hover:bg-zinc-800 transition-colors shadow-2xs">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </button>
                        <div x-cloak x-show="open" @click.outside="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
                             class="absolute right-0 mt-2 w-48 bg-white border border-zinc-200/90 rounded-xl shadow-lg py-1 z-50">
                            <div class="px-3 py-2 border-b border-zinc-100">
                                <p class="font-semibold text-zinc-900 truncate text-xs">{{ auth()->user()->name ?? 'Admin' }}</p>
                                <p class="text-zinc-400 text-[11px] truncate">{{ auth()->user()->email ?? '' }}</p>
                            </div>
                            <a href="{{ route('settings.index') }}" class="block px-3 py-1.5 text-xs text-zinc-700 hover:bg-zinc-50 transition-colors">Settings</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-zinc-50 transition-colors font-medium">Sign Out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="p-4 lg:p-5 max-w-[1400px]">
                @if (session('success'))
                    <div class="mb-5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 px-3.5 py-2.5 text-xs font-medium">
                        {{ session('success') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>