<section class="section">
    <table class="columns-table">
        <tr>
            @foreach ($section['columns'] ?? [] as $column)
                <td class="column-card" style="width: {{ count($section['columns'] ?? []) > 1 ? '50%' : '100%' }};">
                    <h3 class="column-title">{{ $column['title'] ?? '' }}</h3>
                    <p class="column-body">{{ $column['body'] ?? '' }}</p>
                </td>
            @endforeach
        </tr>
    </table>
</section>
