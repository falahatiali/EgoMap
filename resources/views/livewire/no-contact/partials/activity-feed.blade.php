@if ($activityFeed !== [])
    <section class="eg-gm-activity eg-glass eg-gm-activity--rail mb-0" aria-label="{{ __('no_contact.activity_feed_aria') }}">
        <h3 class="h6 mb-3">
            <i class="fa-solid fa-clock-rotate-left me-2" aria-hidden="true"></i>
            {{ __('no_contact.activity_feed_title') }}
        </h3>
        <ul class="eg-gm-activity__list mb-0">
            @foreach ($activityFeed as $entry)
                <li wire:key="activity-{{ $entry['id'] ?? $loop->index }}" @class(['eg-gm-activity__item', 'eg-gm-activity__item--neg' => ($entry['points_delta'] ?? 0) < 0 || ($entry['coins_delta'] ?? 0) < 0])>
                    <span class="eg-gm-activity__event">{{ $entry['event_label'] ?? ($entry['event'] ?? '') }}</span>
                    <span class="eg-gm-activity__deltas">
                        @if (($entry['points_delta'] ?? 0) !== 0)
                            <span>{{ ($entry['points_delta'] > 0 ? '+' : '') . eg_num($entry['points_delta']) }} pts</span>
                        @endif
                        @if (($entry['coins_delta'] ?? 0) !== 0)
                            <span>{{ ($entry['coins_delta'] > 0 ? '+' : '') . eg_num($entry['coins_delta']) }} <i class="fa-solid fa-coins"></i></span>
                        @endif
                    </span>
                    <time class="eg-gm-activity__time small eg-text-muted" datetime="{{ $entry['created_at'] ?? '' }}">{{ $entry['created_at_human'] ?? $entry['created_at'] ?? '' }}</time>
                </li>
            @endforeach
        </ul>
    </section>
@endif
