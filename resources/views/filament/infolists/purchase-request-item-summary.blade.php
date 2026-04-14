<div class="space-y-1">
    @if (filled($itemName))
        <div class="font-medium text-gray-950 dark:text-white">
            {{ $itemName }}
        </div>
    @endif

    @if (filled($description))
        <div class="text-sm leading-5 text-gray-500 dark:text-gray-400">
            {!! nl2br($description) !!}
        </div>
    @endif
</div>