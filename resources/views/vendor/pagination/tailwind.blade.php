@if ($paginator->hasPages())
<nav role="navigation" aria-label="Pagination Navigation"
     class="pagination mt-4 flex items-center justify-between">
    {{-- Info kiri --}}
    <div class="hidden sm:block text-sm text-gray-600 dark:text-gray-300">
        @if ($paginator->firstItem())
            {!! __('Showing') !!}
            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $paginator->firstItem() }}</span>
            {!! __('to') !!}
            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $paginator->lastItem() }}</span>
            {!! __('of') !!}
            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $paginator->total() }}</span>
            {!! __('results') !!}
        @else
            {!! __('Showing') !!} <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $paginator->count() }}</span>
            {!! __('results') !!}
        @endif
    </div>

    {{-- Pager kanan --}}
    <div class="flex items-center gap-1">
        {{-- Prev --}}
        @if ($paginator->onFirstPage())
            <span class="min-w-9 h-9 grid place-items-center rounded-lg border border-gray-300 text-gray-400 dark:border-gray-700 dark:text-gray-500 select-none">
                &lsaquo;
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}"
               class="min-w-9 h-9 grid place-items-center rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700 transition"
               rel="prev"
               aria-label="@lang('pagination.previous')"
               data-page="{{ $paginator->currentPage() - 1 }}"
            >&lsaquo;</a>
        @endif

        {{-- Links --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="min-w-9 h-9 grid place-items-center text-gray-400 dark:text-gray-500 select-none">…</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page"
                              class="min-w-9 h-9 grid place-items-center rounded-lg border border-blue-600 bg-blue-600 text-white dark:border-blue-500 dark:bg-blue-500 font-semibold select-none">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}"
                           class="min-w-9 h-9 grid place-items-center rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700 transition"
                           aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                           data-page="{{ $page }}"
                        >{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"
               class="min-w-9 h-9 grid place-items-center rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700 transition"
               rel="next"
               aria-label="@lang('pagination.next')"
               data-page="{{ $paginator->currentPage() + 1 }}"
            >&rsaquo;</a>
        @else
            <span class="min-w-9 h-9 grid place-items-center rounded-lg border border-gray-300 text-gray-400 dark:border-gray-700 dark:text-gray-500 select-none">
                &rsaquo;
            </span>
        @endif
    </div>
</nav>
@endif
