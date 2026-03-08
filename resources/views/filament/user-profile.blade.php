<div class="flex items-center gap-3 py-2 min-w-[200px]"> {{-- Beri lebar minimal agar tidak terlalu sempit --}}
    <img src="{{ $getRecord()->user->getFilamentAvatarUrl() }}" alt="{{ $getRecord()->user->name }}"
        class="w-10 h-10 rounded-full object-cover flex-shrink-0" {{-- flex-shrink-0 agar gambar tidak gepeng --}}
        loading="lazy">

    <div class="flex flex-col min-w-0"> {{-- min-w-0 ini kunci agar truncate bekerja di dalam flex --}}
        <span class="text-sm font-medium text-gray-900 dark:text-white truncate">
            {{ $getRecord()->user->name }}
        </span>
        {{-- Jika ada info tambahan di bawahnya --}}
        {{-- @if(isset($getRecord()->user->email))
        <span class="text-xs text-gray-500 truncate">
            {{ $getRecord()->user->email }}
        </span>
        @endif --}}
    </div>
</div>