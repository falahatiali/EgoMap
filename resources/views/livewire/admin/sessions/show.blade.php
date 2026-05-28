@php
    $report = $resultPreview['report'] ?? [];
@endphp

<div class="eg-admin-page">
    @include('partials.admin.page-head', [
        'title' => __('admin.sessions.show_title'),
        'subtitle' => $session->uuid,
        'backRoute' => 'admin.sessions.index',
        'backLabel' => __('admin.sessions.back_to_list'),
    ])

    <div class="eg-admin-panels">
        <article class="eg-admin-panel">
            <h3 class="eg-admin-panel-title">{{ __('admin.sessions.overview') }}</h3>
            <dl class="eg-admin-dl">
                <dt>{{ __('admin.table.quiz') }}</dt>
                <dd>{{ $session->quiz?->getTranslation('name', 'en', true) ?? '—' }} <span class="eg-admin-table-muted">({{ $session->quiz?->slug }})</span></dd>
                <dt>{{ __('admin.table.account') }}</dt>
                <dd>
                    @if ($session->user)
                        <a href="{{ route('admin.users.edit', $session->user) }}">{{ $session->user->email }}</a>
                    @else
                        {{ $session->email ?? __('admin.anonymous') }}
                    @endif
                </dd>
                <dt>{{ __('admin.table.status') }}</dt>
                <dd>{{ __('admin.session_status.'.$session->status->value) }}</dd>
                <dt>{{ __('admin.sessions.locale') }}</dt>
                <dd>{{ $session->locale ?? '—' }}</dd>
                <dt>{{ __('admin.sessions.started') }}</dt>
                <dd>{{ $session->started_at?->format('M j, Y H:i') ?? '—' }}</dd>
                <dt>{{ __('admin.sessions.completed') }}</dt>
                <dd>{{ $session->completed_at?->format('M j, Y H:i') ?? '—' }}</dd>
            </dl>

            @if ($session->status->value !== 'abandoned')
                <button type="button" class="eg-admin-btn eg-admin-btn--danger mt-3" wire:click="markAbandoned" wire:confirm="{{ __('admin.sessions.abandon_confirm') }}">
                    {{ __('admin.sessions.mark_abandoned') }}
                </button>
            @endif
        </article>

        <article class="eg-admin-panel">
            <h3 class="eg-admin-panel-title">{{ __('admin.sessions.result') }}</h3>
            @if ($resultPreview)
                <p class="eg-admin-table-primary mb-1">{{ $report['title'] ?? ($report['type_code'] ?? '—') }}</p>
                <p class="eg-admin-page-sub">{{ $report['summary'] ?? ($resultPreview['content']['tagline'] ?? '') }}</p>
                @if (! empty($report['type_code']))
                    <span class="eg-admin-tag">{{ strtoupper($report['type_code']) }}</span>
                @endif
            @else
                <p class="eg-admin-table-muted mb-0">{{ __('admin.sessions.no_result') }}</p>
            @endif
        </article>
    </div>

    <article class="eg-admin-panel">
        <h3 class="eg-admin-panel-title">{{ __('admin.sessions.responses') }} ({{ $session->responses->count() }})</h3>
        @if ($session->responses->isEmpty())
            <p class="eg-admin-table-muted mb-0">{{ __('admin.sessions.no_responses') }}</p>
        @else
            <div class="eg-admin-table-wrap">
                <table class="eg-admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('admin.sessions.answer') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($session->responses as $response)
                            <tr>
                                <td>{{ $response->id }}</td>
                                <td class="eg-admin-table-mono">{{ json_encode($response->value) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </article>
</div>
