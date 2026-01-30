<div class="space-y-2">
    @php
        $activities = $getState() ?? collect();
    @endphp
    
    @if($activities->isEmpty())
        <div class="text-sm text-gray-500 dark:text-gray-400 italic">
            No changes recorded yet
        </div>
    @else
        @foreach($activities as $activity)
            <div class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="flex-shrink-0 mt-0.5">
                    @if($activity->event === 'created')
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    @elseif($activity->event === 'updated')
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    @elseif($activity->event === 'deleted')
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    @else
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ ucfirst($activity->event) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $activity->created_at->diffForHumans() }}
                        </p>
                    </div>
                    @if($activity->properties && $activity->properties->has('attributes'))
                        <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                            @foreach($activity->properties->get('attributes') as $key => $value)
                                @if($activity->properties->has('old') && isset($activity->properties->get('old')[$key]))
                                    <div class="flex items-center space-x-2">
                                        <span class="font-semibold">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                        <span class="text-red-600 dark:text-red-400 line-through">{{ $activity->properties->get('old')[$key] }}</span>
                                        <span>→</span>
                                        <span class="text-green-600 dark:text-green-400">{{ $value }}</span>
                                    </div>
                                @else
                                    <div>
                                        <span class="font-semibold">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                        <span>{{ $value }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                    @if($activity->causer)
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            by {{ $activity->causer->name }}
                        </p>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
