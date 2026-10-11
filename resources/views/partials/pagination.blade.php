@if ($paginator->hasPages())
    <nav aria-label="Strony wyników">
        <ul>
            @if ($paginator->onFirstPage())
                <li class="disabled"><span aria-hidden="true">&laquo;</span><span class="sr-only">Poprzednia (brak)</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo;<span class="sr-only"> Poprzednia strona</span></a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="disabled"><span>{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="active"><span aria-current="page"><span class="sr-only">Strona </span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}"><span class="sr-only">Strona </span>{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next"><span class="sr-only">Następna strona </span>&raquo;</a></li>
            @else
                <li class="disabled"><span aria-hidden="true">&raquo;</span><span class="sr-only">Następna (brak)</span></li>
            @endif
        </ul>
    </nav>
@endif
