<div class="flex items-center gap-3 px-3 py-2">
    <img src="{{ $getRecord()->user->getFilamentAvatarUrl() }}"
        alt="{{ $getRecord()->user->name }}" class="w-10 h-10 rounded-full object-cover" loading="lazy">
    <div class="flex flex-col">
        <span class="text-sm font-medium text-gray-900 dark:text-white">
            {{ $getRecord()->user->name }}
        </span>
    </div>
</div>