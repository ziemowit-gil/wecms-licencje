@if ($activities->isEmpty())
    <p class="muted">Brak wpisów.</p>
@else
    <div class="table-wrap">
        <table>
            <thead><tr><th scope="col">Kiedy</th><th scope="col">Kto</th><th scope="col">Co</th></tr></thead>
            <tbody>
                @foreach ($activities as $activity)
                    <tr>
                        <td><time datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->format('d.m.Y H:i') }}</time></td>
                        <td>{{ $activity->user?->name ?? 'system' }}</td>
                        <td>{{ $activity->description }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
