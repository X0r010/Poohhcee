@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="space-y-6" x-data="{ activeTab: 'general' }">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-zinc-900 tracking-tight">Settings & Configuration</h1>
            <p class="text-xs text-zinc-500">Manage your business details, default preferences, shirt options, and security.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs rounded-lg font-medium">
            {{ session('success') }}
        </div>
    @endif

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Left Column: Business Information --}}
        <div class="bg-white border border-zinc-200/80 rounded-xl shadow-2xs overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-zinc-100 bg-zinc-50/50">
                <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Business Information</h2>
            </div>
            
            <form action="{{ route('settings.update') }}" method="POST" class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Business Name</label>
                        <input type="text" name="business_name" value="{{ old('business_name', $settings['business_name']) }}" required
                               class="w-full bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-800 focus:outline-none focus:border-zinc-400">
                        @error('business_name') <span class="text-red-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 mb-1">Phone</label>
                            <input type="text" name="business_phone" value="{{ old('business_phone', $settings['business_phone']) }}"
                                   class="w-full bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-800 focus:outline-none focus:border-zinc-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 mb-1">Email</label>
                            <input type="email" name="business_email" value="{{ old('business_email', $settings['business_email']) }}"
                                   class="w-full bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-800 focus:outline-none focus:border-zinc-400">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Address</label>
                        <input type="text" name="business_address" value="{{ old('business_address', $settings['business_address']) }}"
                               class="w-full bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-800 focus:outline-none focus:border-zinc-400">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 mb-1">Default Source</label>
                            <select name="default_source" class="w-full bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-800 focus:outline-none focus:border-zinc-400">
                                @foreach(['TikTok', 'Instagram', 'Website', 'Walk-in', 'Other'] as $source)
                                    <option value="{{ $source }}" {{ $settings['default_source'] === $source ? 'selected' : '' }}>{{ $source }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 mb-1">Low Stock Threshold</label>
                            <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $settings['low_stock_threshold']) }}" min="0" required
                                   class="w-full bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-800 focus:outline-none focus:border-zinc-400">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Default Payment Method</label>
                        <input type="text" name="default_payment_method" value="{{ old('default_payment_method', $settings['default_payment_method']) }}"
                               class="w-full bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-800 focus:outline-none focus:border-zinc-400">
                    </div>
                </div>

                <div class="pt-4 border-t border-zinc-100 flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-zinc-900 hover:bg-zinc-800 text-white rounded-lg text-xs font-semibold transition-colors">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>

        {{-- Right Column: Security & Password --}}
        <div class="bg-white border border-zinc-200/80 rounded-xl shadow-2xs overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-zinc-100 bg-zinc-50/50">
                <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Update Password</h2>
            </div>
            
            <form action="{{ route('settings.password') }}" method="POST" class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Current Password</label>
                        <input type="password" name="current_password" required
                               class="w-full bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-800 focus:outline-none focus:border-zinc-400">
                        @error('current_password') <span class="text-red-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">New Password</label>
                        <input type="password" name="password" required
                               class="w-full bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-800 focus:outline-none focus:border-zinc-400">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Confirm New Password</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-800 focus:outline-none focus:border-zinc-400">
                        @error('password') <span class="text-red-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="pt-4 border-t border-zinc-100 flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-zinc-900 hover:bg-zinc-800 text-white rounded-lg text-xs font-semibold transition-colors">
                        Update Password
                    </button>
                </div>
            </form>
        </div>

    </div>

    {{-- Management Row (Shirt Types & Colors) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Shirt Types Management --}}
        <div class="bg-white border border-zinc-200/80 rounded-xl shadow-2xs overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-zinc-100 bg-zinc-50/50 flex justify-between items-center">
                <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Shirt Types</h2>
                <span class="bg-zinc-200 text-zinc-700 rounded-full px-2 py-0.5 text-[10px] font-bold">{{ count($shirtTypes) }} Types</span>
            </div>
            
            <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                <form action="{{ route('shirt-types.store') }}" method="POST" class="flex gap-2">
                    @csrf
                    <input type="text" name="name" placeholder="New shirt type name..." required
                           class="flex-1 bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-800 focus:outline-none focus:border-zinc-400">
                    <button type="submit" class="px-4 py-2 bg-zinc-900 hover:bg-zinc-800 text-white rounded-lg text-xs font-semibold">Add Type</button>
                </form>

                <div class="divide-y divide-zinc-50 max-h-48 overflow-y-auto border border-zinc-100 rounded-lg px-3">
                    @forelse($shirtTypes as $type)
                        <div class="py-2.5 flex justify-between items-center text-xs">
                            <span class="font-medium text-zinc-800">{{ $type->name }}</span>
                            <form action="{{ route('shirt-types.destroy', $type) }}" method="POST" onsubmit="return confirm('Delete this shirt type?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 font-semibold text-[11px]">Remove</button>
                            </form>
                        </div>
                    @empty
                        <p class="py-4 text-center text-zinc-400 text-xs">No shirt types found.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Shirt Colors Management --}}
        <div class="bg-white border border-zinc-200/80 rounded-xl shadow-2xs overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-zinc-100 bg-zinc-50/50 flex justify-between items-center">
                <h2 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Shirt Colors</h2>
                <span class="bg-zinc-200 text-zinc-700 rounded-full px-2 py-0.5 text-[10px] font-bold">{{ count($shirtColors) }} Colors</span>
            </div>
            
            <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                <form action="{{ route('shirt-colors.store') }}" method="POST" class="flex gap-2">
                    @csrf
                    <input type="text" name="name" placeholder="New color name..." required
                           class="flex-1 bg-zinc-50 border border-zinc-200 rounded-lg px-3 py-2 text-xs text-zinc-800 focus:outline-none focus:border-zinc-400">
                    <button type="submit" class="px-4 py-2 bg-zinc-900 hover:bg-zinc-800 text-white rounded-lg text-xs font-semibold">Add Color</button>
                </form>

                <div class="divide-y divide-zinc-50 max-h-48 overflow-y-auto border border-zinc-100 rounded-lg px-3">
                    @forelse($shirtColors as $color)
                        <div class="py-2.5 flex justify-between items-center text-xs">
                            <span class="font-medium text-zinc-800">{{ $color->name }}</span>
                            <form action="{{ route('shirt-colors.destroy', $color) }}" method="POST" onsubmit="return confirm('Delete this color?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 font-semibold text-[11px]">Remove</button>
                            </form>
                        </div>
                    @empty
                        <p class="py-4 text-center text-zinc-400 text-xs">No shirt colors found.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

</div>
@endsection