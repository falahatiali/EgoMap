<table class="stat-table" cellpadding="0" cellspacing="0">
    <tr>
        @foreach ($section['items'] ?? [] as $item)
            <td @class(['stat-card', $item['tone'] ?? 'neutral'])>
                <div class="stat-label">{{ $item['label'] ?? '' }}</div>
                <div class="stat-value">{{ $item['value'] ?? '' }}</div>
            </td>
        @endforeach
    </tr>
</table>
