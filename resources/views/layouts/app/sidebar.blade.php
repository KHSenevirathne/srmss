<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-950">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    @can('view-reports')
                        <flux:sidebar.item icon="chart-bar" :href="route('reports')" :current="request()->routeIs('reports')" wire:navigate>
                            {{ __('Reports') }}
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>

                {{-- Operations — each item is shown only if the user holds the guarding permission. --}}
                <flux:sidebar.group :heading="__('Operations')" class="grid">
                    @can('manage-fleet')
                        <flux:sidebar.item icon="truck" :href="route('vehicles')" :current="request()->routeIs('vehicles')" wire:navigate>
                            {{ __('Vehicles') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="identification" :href="route('drivers')" :current="request()->routeIs('drivers')" wire:navigate>
                            {{ __('Drivers') }}
                        </flux:sidebar.item>
                    @endcan
                    @can('manage-routes')
                        <flux:sidebar.item icon="map" :href="route('routes')" :current="request()->routeIs('routes')" wire:navigate>
                            {{ __('Routes') }}
                        </flux:sidebar.item>
                    @endcan
                    @can('manage-schedules')
                        <flux:sidebar.item icon="calendar-days" :href="route('schedules')" :current="request()->routeIs('schedules')" wire:navigate>
                            {{ __('Schedules') }}
                        </flux:sidebar.item>
                    @endcan
                    @can('log-fuel')
                        <flux:sidebar.item icon="beaker" :href="route('fuel-logs')" :current="request()->routeIs('fuel-logs')" wire:navigate>
                            {{ __('Fuel Logs') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="wrench-screwdriver" :href="route('maintenance-logs')" :current="request()->routeIs('maintenance-logs')" wire:navigate>
                            {{ __('Maintenance') }}
                        </flux:sidebar.item>
                    @endcan
                    {{-- Phase 4+ : Schedules, Reports link here, each behind its own @can. --}}
                </flux:sidebar.group>

                {{-- Administration — admin only. --}}
                @can('manage-users')
                    <flux:sidebar.group :heading="__('Administration')" class="grid">
                        <flux:sidebar.item icon="users" :href="route('users')" :current="request()->routeIs('users')" wire:navigate>
                            {{ __('Users') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('activity-log')" :current="request()->routeIs('activity-log')" wire:navigate>
                            {{ __('Activity Log') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
