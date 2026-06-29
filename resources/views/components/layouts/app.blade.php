<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ 
    sidebarOpen: window.innerWidth >= 768, 
    profileOpen: false,
    isDesktop: window.innerWidth >= 768,
    init() {
        this.isDesktop = window.innerWidth >= 768;
        this.sidebarOpen = this.isDesktop;
        window.addEventListener('resize', () => {
            this.isDesktop = window.innerWidth >= 768;
            this.sidebarOpen = this.isDesktop;
        });
    }
}" :class="{ 'overflow-hidden': sidebarOpen && !isDesktop }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- PWA Meta Tags --}}
    <meta name="theme-color" content="#383f7b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Immos SAM">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="description" content="Immos SAM - Gestion des immobilisations">

    <title>{{ config('app.name', 'Inventaire Pro') }} - @yield('title', 'Dashboard')</title>
    
    {{-- PWA Manifest --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    
    {{-- PWA Icons --}}
    <link rel="icon" type="image/png" href="{{ asset('logo_sam.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo_sam.jpg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    {{-- Alpine.js est déjà inclus dans Livewire 3, ne pas le charger séparément --}}

    <style>
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-secondary-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside 
            x-show="sidebarOpen || isDesktop"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed md:static inset-y-0 left-0 z-50 w-64 bg-indigo-800 text-white flex flex-col"
            :class="{ 'translate-x-0': isDesktop || sidebarOpen }"
        >
            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-6 bg-indigo-900 border-b border-indigo-700">
                <div class="flex items-center space-x-2">
                    <div class="flex flex-col leading-tight">
                        <span class="font-bold text-lg">Immos SAM</span>
                        <span class="text-[10px] text-gray-400 uppercase tracking-wider">Immobilisations</span>
                    </div>
                </div>
                <button @click="sidebarOpen = false" class="md:hidden text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-4 px-3" x-data="{ openMenu: '{{ request()->routeIs('biens.*') ? 'immobilisations' : (request()->routeIs('localisations.*') || request()->routeIs('affectations.*') || request()->routeIs('emplacements.*') || request()->routeIs('designations.*') || request()->routeIs('categories.*') ? 'parametres' : '') }}' }">
                <ul class="space-y-1">
                    <!-- Dashboard -->
                    <li>
                        <a href="{{ route('dashboard') }}" 
                           class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-indigo-700 hover:text-white transition-colors {{ request()->routeIs('dashboard') ? 'bg-indigo-700 text-white' : '' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    @auth
                        @if(auth()->user()->canManageInventaire())
                            <!-- IMMOBILISATIONS - Accordéon -->
                            <li>
                                <button @click="openMenu = (openMenu === 'immobilisations') ? '' : 'immobilisations'" 
                                        class="w-full flex items-center justify-between px-4 py-3 text-gray-300 rounded-lg hover:bg-indigo-700 hover:text-white transition-colors"
                                        :class="{ 'bg-indigo-700 text-white': openMenu === 'immobilisations' }">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                        <span>Immobilisations</span>
                                    </div>
                                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': openMenu === 'immobilisations' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <ul x-show="openMenu === 'immobilisations'" x-transition class="mt-2 space-y-1 pl-4">
                                    <li>
                                        <a href="{{ route('biens.index') }}" 
                                           class="flex items-center px-4 py-2 text-sm text-gray-400 rounded-lg hover:bg-indigo-700 hover:text-white transition-colors {{ request()->routeIs('biens.index') || request()->routeIs('biens.show') ? 'bg-indigo-700 text-white' : '' }}">
                                            <span class="mr-2">📋</span>
                                            <span>Liste</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('biens.create') }}" 
                                           class="flex items-center px-4 py-2 text-sm text-gray-400 rounded-lg hover:bg-indigo-700 hover:text-white transition-colors {{ request()->routeIs('biens.create') || request()->routeIs('biens.edit') ? 'bg-indigo-700 text-white' : '' }}">
                                            <span class="mr-2">➕</span>
                                            <span>Ajouter</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('biens.transfert') }}" 
                                           class="flex items-center px-4 py-2 text-sm text-gray-400 rounded-lg hover:bg-indigo-700 hover:text-white transition-colors {{ request()->routeIs('biens.transfert') ? 'bg-indigo-700 text-white' : '' }}">
                                            <span class="mr-2">🔄</span>
                                            <span>Transfert</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('biens.transfert.historique') }}" 
                                           class="flex items-center px-4 py-2 text-sm text-gray-400 rounded-lg hover:bg-indigo-700 hover:text-white transition-colors {{ request()->routeIs('biens.transfert.historique') ? 'bg-indigo-700 text-white' : '' }}">
                                            <span class="mr-2">📜</span>
                                            <span>Historique Transferts</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('biens.amortissements') }}" 
                                           class="flex items-center px-4 py-2 text-sm text-gray-400 rounded-lg hover:bg-indigo-700 hover:text-white transition-colors {{ request()->routeIs('biens.amortissements') ? 'bg-indigo-700 text-white' : '' }}">
                                            <span class="mr-2">📊</span>
                                            <span>Amortissements</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <!-- PARAMETRES - Accordéon -->
                            <li>
                                <button @click="openMenu = (openMenu === 'parametres') ? '' : 'parametres'" 
                                        class="w-full flex items-center justify-between px-4 py-3 text-gray-300 rounded-lg hover:bg-indigo-700 hover:text-white transition-colors"
                                        :class="{ 'bg-indigo-700 text-white': openMenu === 'parametres' }">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <span>Paramètres</span>
                                    </div>
                                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': openMenu === 'parametres' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <ul x-show="openMenu === 'parametres'" x-transition class="mt-2 space-y-1 pl-4">
                                    <li>
                                        <a href="{{ route('localisations.index') }}" 
                                           class="flex items-center px-4 py-2 text-sm text-gray-400 rounded-lg hover:bg-indigo-700 hover:text-white transition-colors {{ request()->routeIs('localisations.*') ? 'bg-indigo-700 text-white' : '' }}">
                                            <span class="mr-2">📍</span>
                                            <span>Localisations</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('affectations.index') }}" 
                                           class="flex items-center px-4 py-2 text-sm text-gray-400 rounded-lg hover:bg-indigo-700 hover:text-white transition-colors {{ request()->routeIs('affectations.*') ? 'bg-indigo-700 text-white' : '' }}">
                                            <span class="mr-2">🏢</span>
                                            <span>Affectations</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('emplacements.index') }}" 
                                           class="flex items-center px-4 py-2 text-sm text-gray-400 rounded-lg hover:bg-indigo-700 hover:text-white transition-colors {{ request()->routeIs('emplacements.*') ? 'bg-indigo-700 text-white' : '' }}">
                                            <span class="mr-2">🏠</span>
                                            <span>Emplacements</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('categories.index') }}"
                                           class="flex items-center px-4 py-2 text-sm text-gray-400 rounded-lg hover:bg-indigo-700 hover:text-white transition-colors {{ request()->routeIs('categories.*') ? 'bg-indigo-700 text-white' : '' }}">
                                            <span class="mr-2">🏷️</span>
                                            <span>Catégories</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('designations.index') }}"
                                           class="flex items-center px-4 py-2 text-sm text-gray-400 rounded-lg hover:bg-indigo-700 hover:text-white transition-colors {{ request()->routeIs('designations.*') ? 'bg-indigo-700 text-white' : '' }}">
                                            <span class="mr-2">📝</span>
                                            <span>Désignations</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <!-- Inventaires -->
                            <li>
                                <a href="{{ route('inventaires.index') }}" 
                                   class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-indigo-700 hover:text-white transition-colors {{ request()->routeIs('inventaires.*') ? 'bg-indigo-700 text-white' : '' }}">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    <span>Inventaires</span>
                                </a>
                            </li>
                        @endif

                        @if(auth()->check() && auth()->user()->isAdmin())
                            <!-- Utilisateurs -->
                            <li>
                                <a href="{{ route('users.index') }}" 
                                   class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-indigo-700 hover:text-white transition-colors {{ request()->routeIs('users.*') ? 'bg-indigo-700 text-white' : '' }}">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                    <span>Utilisateurs</span>
                                </a>
                            </li>
                        @endif
                    @endauth
                </ul>
            </nav>

            <!-- Footer Sidebar -->
            <div class="px-4 py-4 border-t border-indigo-700">
                <div class="text-xs text-gray-400 mb-2">
                    Version 1.0.0
                </div>
            </div>
        </aside>

        <!-- Overlay mobile -->
        <div 
            x-show="sidebarOpen"
            @click="sidebarOpen = false"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-600 bg-opacity-75 z-40 md:hidden"
            x-cloak
        ></div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white border-b border-gray-200 shadow-sm h-16 flex items-center justify-between px-4 md:px-6 z-30">
                <!-- Left: Hamburger -->
                <div class="flex items-center space-x-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>

                <!-- Right: Notifications + Profile -->
                <div class="flex items-center space-x-4">
                    @auth
                        <!-- Profile Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button 
                                @click="open = !open"
                                class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 transition-colors"
                            >
                                <div class="flex items-center space-x-2">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                                        {{ strtoupper(substr(auth()->user()->users ?? 'U', 0, 1)) }}
                                    </div>
                                    <div class="hidden md:block text-left">
                                        <div class="text-sm font-medium text-gray-900">{{ auth()->user()->users ?? 'Utilisateur' }}</div>
                                        <div class="text-xs text-gray-500">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ auth()->user()->role === 'admin' ? 'bg-purple-100 text-purple-800' : (auth()->user()->role === 'admin_stock' ? 'bg-indigo-100 text-indigo-800' : 'bg-blue-100 text-blue-800') }}">
                                                {{ auth()->user()->role_name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div 
                                x-show="open"
                                @click.away="open = false"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50 border border-gray-200"
                                x-cloak
                            >
                                <a href="{{ route('profil') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Mon profil</a>
                                <hr class="my-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Déconnexion
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endauth
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-secondary-50">
                <!-- Main Content -->
                <div class="p-4 md:p-6">
                    {{ $slot }}
                </div>

                <!-- Footer -->
                <footer class="bg-white border-t border-gray-200 px-4 md:px-6 py-4 mt-auto">
                    <p class="text-sm text-gray-500 text-center">© 2025 Immos SAM</p>
                </footer>
            </main>
        </div>
    </div>

    @livewireScripts
    
    @stack('scripts')
    
    {{-- PWA Service Worker Registration --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('{{ asset('sw.js') }}')
                    .then((registration) => {
                        console.log('✅ Service Worker enregistré:', registration.scope);
                        setInterval(() => registration.update(), 60000);
                    })
                    .catch((error) => {
                        console.error('❌ Erreur Service Worker:', error);
                    });
            });
        }
    </script>
</body>
</html>

