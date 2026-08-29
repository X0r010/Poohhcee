@extends('layouts.app')

@section('title', 'Collections')

@section('header-actions')
    <button type="button" onclick="document.getElementById('new-collection-form').classList.toggle('hidden')"
            class="w-full sm:w-auto inline-flex items-center justify-center h-9 px-4 rounded-lg bg-black hover:bg-zinc-800 text-white text-xs font-semibold shadow-xs transition-colors">
        + New Artist
    </button>
@endsection

@section('content')
<div class="space-y-5">

    <div>
        <h2 class="text-sm font-bold text-zinc-900">Artist Collections</h2>
        <p class="text-xs text-zinc-500 mt-0.5">Add artist → add designs inside → film slots auto-created in DTF Films</p>
    </div>

    {{-- ── New Collection form (hidden by default) ──────────────── --}}
    <div id="new-collection-form" class="hidden bg-white border border-zinc-200/80 rounded-xl p-4 sm:p-5 shadow-2xs">
        <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-4">New Artist Collection</h3>
        <form method="POST" action="{{ route('collections.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
            @csrf
            <div>
                <label class="block text-xs font-medium text-zinc-700 mb-1.5">Artist Name</label>
                <input type="text" name="name" required placeholder="e.g. Clairo, Billie Eilish..."
                       class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-700 mb-1.5">Description (optional)</label>
                <input type="text" name="description" placeholder="Optional..."
                       class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs focus:outline-none focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="w-full sm:w-auto h-9 px-4 rounded-lg bg-black text-white text-xs font-semibold">Create</button>
                <button type="button" onclick="document.getElementById('new-collection-form').classList.add('hidden')"
                        class="w-full sm:w-auto h-9 px-4 rounded-lg bg-white border border-zinc-200 text-zinc-700 text-xs font-medium">Cancel</button>
            </div>
        </form>
    </div>

    {{-- ── Collections list ────────────────────────────────────── --}}
    @forelse ($collections as $collection)
        <div class="bg-white border border-zinc-200/80 rounded-xl p-4 sm:p-5 shadow-2xs {{ !$collection->active ? 'opacity-50' : '' }}">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4 mb-4">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-sm font-bold text-zinc-900">{{ $collection->name }}</h3>
                    <span class="badge badge-unknown">{{ $collection->designs->count() }} designs</span>
                    <span class="badge badge-unknown">{{ $collection->orders_count }} orders</span>
                </div>
                <form method="POST" action="{{ route('collections.update', $collection) }}" class="self-end sm:self-auto">
                    @csrf @method('PUT')
                    <input type="hidden" name="toggle_active" value="1">
                    <button type="submit" class="text-xs font-medium text-zinc-500 hover:text-zinc-900">
                        {{ $collection->active ? 'Deactivate' : 'Activate' }}
                    </button>
                </form>
            </div>

            <div class="space-y-1.5 mb-4">
                @forelse ($collection->designs as $design)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 rounded-lg px-3 py-2 hover:bg-zinc-50 {{ !$design->active ? 'opacity-50' : '' }}">
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="font-medium text-zinc-800">{{ $design->name }}</span>
                            @if ($design->has_front)<span class="badge badge-ready">Front</span>@endif
                            @if ($design->has_back)<span class="badge badge-ready">Back</span>@endif
                            @if ($design->printArtwork && $design->printArtwork->designs->count() > 1)
                                <span class="badge badge-unknown">shares film</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between sm:justify-end gap-3 text-xs shrink-0">
                            <span class="text-zinc-400">{{ $design->orders_count ?? $design->orders->count() }} orders</span>
                            <form method="POST" action="{{ route('designs.update', $design) }}">
                                @csrf @method('PUT')
                                <button type="submit" class="font-medium text-zinc-400 hover:text-zinc-900">
                                    {{ $design->active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-zinc-400 px-3 py-2">No designs yet.</p>
                @endforelse
            </div>

            {{-- ── Add Design row ──────────────────────────────── --}}
            <form method="POST" action="{{ route('collections.designs.add', $collection) }}"
                  class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-2.5 items-end pt-3 border-t border-zinc-100"
                  x-data="{ sameFilm: '' }">
                @csrf
                <div class="sm:col-span-2 md:col-span-2">
                    <label class="block text-[10px] font-medium text-zinc-500 mb-1">Add Design</label>
                    <input type="text" name="name" required placeholder="e.g. Charm, Second Nature, Never Enough..."
                           class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs focus:outline-none focus:border-zinc-900">
                </div>
                <div class="sm:col-span-1 md:col-span-1">
                    <label class="block text-[10px] font-medium text-zinc-500 mb-1">Same Film As</label>
                    <select name="uses_same_film_as" x-model="sameFilm" class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs focus:outline-none focus:border-zinc-900">
                        <option value="">None (new artwork)</option>
                        @foreach ($collection->designs as $existing)
                            <option value="{{ $existing->id }}">{{ $existing->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="!sameFilm" class="sm:col-span-1 md:col-span-1">
                    <label class="block text-[10px] font-medium text-zinc-500 mb-1">Front Print?</label>
                    <select name="has_front" class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs focus:outline-none focus:border-zinc-900">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div x-show="!sameFilm" class="sm:col-span-1 md:col-span-1">
                    <label class="block text-[10px] font-medium text-zinc-500 mb-1">Back Print?</label>
                    <select name="has_back" class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs focus:outline-none focus:border-zinc-900">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <button type="submit" class="w-full h-8 px-3 rounded-md bg-white border border-zinc-200/90 text-zinc-700 hover:bg-zinc-100 text-xs font-medium shadow-2xs sm:col-span-1 md:col-span-1 whitespace-nowrap">+ Add Design</button>
            </form>
        </div>
    @empty
        <div class="bg-white border border-zinc-200/80 rounded-xl p-8 shadow-2xs text-center">
            <p class="text-sm text-zinc-500">No collections yet — click "+ New Artist" to add your first one.</p>
        </div>
    @endforelse
</div>
@endsection