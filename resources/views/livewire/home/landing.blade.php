<div class="eg-landing rh-page rh-page--premium">
    @include('livewire.home.landing-hero')

    <section class="rh-section" id="how">
        <div class="rh-section__intro">
            <h2 class="rh-heading" data-i18n="landing.steps_title">{{ __('landing.steps_title') }}</h2>
            <p class="rh-text-muted" data-i18n="landing.steps_subtitle">{{ __('landing.steps_subtitle') }}</p>
        </div>

        <div class="rh-flow">
            <span data-i18n="landing.flow_checkin">{{ __('landing.flow_checkin') }}</span>
            <i class="fa-solid fa-chevron-right" data-icon-directional aria-hidden="true"></i>
            <span data-i18n="landing.flow_protocol">{{ __('landing.flow_protocol') }}</span>
            <i class="fa-solid fa-chevron-right" data-icon-directional aria-hidden="true"></i>
            <span data-i18n="landing.flow_missions">{{ __('landing.flow_missions') }}</span>
        </div>

        <div class="rh-steps">
            @foreach ([1, 2, 3] as $n)
                <article class="rh-card rh-card--step">
                    <span class="rh-card__index">{{ $n }}</span>
                    <h3 data-i18n="landing.step{{ $n }}_title">{{ __("landing.step{$n}_title") }}</h3>
                    <p data-i18n="landing.step{{ $n }}_desc">{{ __("landing.step{$n}_desc") }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="rh-section rh-section--tint" id="rules">
        <h2 class="rh-heading" data-i18n="landing.dos_title">{{ __('landing.dos_title') }}</h2>
        <div class="rh-split">
            <div class="rh-card rh-card--positive">
                <h3 data-i18n="landing.dos_do_heading">{{ __('landing.dos_do_heading') }}</h3>
                <ul class="rh-list">
                    @foreach (range(1, 6) as $i)
                        <li data-i18n="landing.dos_do_{{ $i }}">{{ __("landing.dos_do_{$i}") }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="rh-card rh-card--negative">
                <h3 data-i18n="landing.dos_dont_heading">{{ __('landing.dos_dont_heading') }}</h3>
                <ul class="rh-list">
                    @foreach (range(1, 6) as $i)
                        <li data-i18n="landing.dos_dont_{{ $i }}">{{ __("landing.dos_dont_{$i}") }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <section class="rh-section" id="checkin">
        <div class="rh-section__intro">
            <h2 class="rh-heading" data-i18n="landing.checkin_title">{{ __('landing.checkin_title') }}</h2>
            <p class="rh-text-muted" data-i18n="landing.checkin_subtitle">{{ __('landing.checkin_subtitle') }}</p>
        </div>

        <div class="rh-card rh-card--flat">
            @php
                $previewQuestions = [
                    ['q' => 'checkin_q1', 'opts' => ['checkin_q1_o1', 'checkin_q1_o2', 'checkin_q1_o3']],
                    ['q' => 'checkin_q2', 'opts' => ['checkin_q2_o1', 'checkin_q2_o2', 'checkin_q2_o3']],
                    ['q' => 'checkin_q3', 'opts' => ['checkin_q3_o1', 'checkin_q3_o2']],
                    ['q' => 'checkin_q4', 'opts' => ['checkin_q4_o1', 'checkin_q4_o2', 'checkin_q4_o3']],
                    ['q' => 'checkin_q5', 'opts' => ['checkin_q5_o2', 'checkin_q5_o4']],
                ];
            @endphp
            @foreach ($previewQuestions as $index => $question)
                <div class="rh-checkin-row">
                    <p class="rh-checkin-row__q">
                        <span class="rh-checkin-row__num">{{ $index + 1 }}</span>
                        <span data-i18n="landing.{{ $question['q'] }}">{{ __("landing.{$question['q']}") }}</span>
                    </p>
                    <div class="rh-tags">
                        @foreach ($question['opts'] as $opt)
                            <span class="rh-tag" data-i18n="landing.{{ $opt }}">{{ __("landing.{$opt}") }}</span>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <p class="rh-caption" data-i18n="landing.checkin_preview_label">{{ __('landing.checkin_preview_label') }}</p>
            <button type="button" class="rh-btn rh-btn--primary" wire:click="startCheckIn">
                <span data-i18n="landing.checkin_cta">{{ __('landing.checkin_cta') }}</span>
            </button>
        </div>
    </section>

    <section class="rh-section rh-section--tint" id="profile">
        <h2 class="rh-heading" data-i18n="landing.profile_title">{{ __('landing.profile_title') }}</h2>
        <p class="rh-text-muted" data-i18n="landing.profile_body">{{ __('landing.profile_body') }}</p>
        <p class="rh-label" data-i18n="landing.profile_saves">{{ __('landing.profile_saves') }}</p>
        <ul class="rh-feature-grid">
            @foreach (range(1, 9) as $i)
                <li data-i18n="landing.profile_item_{{ $i }}">{{ __("landing.profile_item_{$i}") }}</li>
            @endforeach
        </ul>
        <p class="rh-caption" data-i18n="landing.profile_note">{{ __('landing.profile_note') }}</p>
        <div class="rh-actions">
            <a href="{{ route('register') }}" class="rh-btn rh-btn--primary" wire:navigate>
                <span data-i18n="landing.profile_cta_primary">{{ __('landing.profile_cta_primary') }}</span>
            </a>
            <button type="button" class="rh-btn rh-btn--secondary" wire:click="startCheckIn">
                <span data-i18n="landing.profile_cta_secondary">{{ __('landing.profile_cta_secondary') }}</span>
            </button>
        </div>
    </section>

    <section class="rh-section" id="protocol-90">
        <h2 class="rh-heading" data-i18n="landing.nc_title">{{ __('landing.nc_title') }}</h2>
        <p class="rh-text-muted" data-i18n="landing.nc_body">{{ __('landing.nc_body') }}</p>
        <p class="rh-callout" data-i18n="landing.nc_reset_note">{{ __('landing.nc_reset_note') }}</p>

        <div class="rh-card rh-card--protocol">
            <div class="rh-protocol-live__head">
                <span class="rh-protocol-live__day">
                    {{ __('landing.nc_day') }} {{ str_pad((string) $ncPreviewDay, 3, '0', STR_PAD_LEFT) }} / {{ $ncPreviewTotal }}
                </span>
                <span class="rh-badge rh-badge--live" data-i18n="landing.nc_status">{{ __('landing.nc_status') }}</span>
            </div>
            <p class="rh-text-muted" data-i18n="landing.nc_risk">{{ __('landing.nc_risk') }}</p>
            <p data-i18n="landing.nc_mission">{{ __('landing.nc_mission') }}</p>
            <a href="#emergency" class="rh-btn rh-btn--danger-outline" data-i18n="landing.nc_emergency_btn">{{ __('landing.nc_emergency_btn') }}</a>
        </div>

        <h3 class="rh-label" data-i18n="landing.nc_rules_title">{{ __('landing.nc_rules_title') }}</h3>
        <ul class="rh-list rh-list--cols">
            @foreach (range(1, 6) as $i)
                <li data-i18n="landing.nc_rule_{{ $i }}">{{ __("landing.nc_rule_{$i}") }}</li>
            @endforeach
        </ul>
        <p class="rh-caption" data-i18n="landing.nc_relapse">{{ __('landing.nc_relapse') }}</p>
        <a href="{{ route('no-contact') }}" class="rh-btn rh-btn--primary" wire:navigate>
            <span data-i18n="landing.nc_cta">{{ __('landing.nc_cta') }}</span>
        </a>
    </section>

    <section class="rh-section rh-emergency" id="emergency">
        <div class="rh-emergency__shell">
            <div class="rh-emergency__head">
                <span class="rh-emergency__icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <div>
                    <h2 class="rh-heading" data-i18n="landing.emergency_title">{{ __('landing.emergency_title') }}</h2>
                    <p class="rh-emergency__lead" data-i18n="landing.emergency_body">{{ __('landing.emergency_body') }}</p>
                </div>
            </div>

            <p class="rh-label" data-i18n="landing.emergency_flow_title">{{ __('landing.emergency_flow_title') }}</p>
            <ol class="rh-emergency__steps">
                @foreach (range(1, 5) as $i)
                    <li>
                        <span class="rh-emergency__step-num">{{ $i }}</span>
                        <span data-i18n="landing.emergency_step_{{ $i }}">{{ __("landing.emergency_step_{$i}") }}</span>
                    </li>
                @endforeach
            </ol>

            <div class="rh-emergency__panel">
                <p class="rh-caption" data-i18n="landing.emergency_placeholder">{{ __('landing.emergency_placeholder') }}</p>
                <div class="rh-textarea-preview" aria-hidden="true"></div>
                <p class="rh-emergency__timer">
                    <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
                    <span data-i18n="landing.emergency_wait">{{ __('landing.emergency_wait') }}</span>
                </p>
            </div>

            <button type="button" class="rh-btn rh-btn--danger rh-btn--lg" wire:click="startCheckIn">
                <span data-i18n="landing.emergency_cta">{{ __('landing.emergency_cta') }}</span>
            </button>
        </div>
    </section>

    <section class="rh-section rh-section--compact" id="missions">
        <h2 class="rh-heading" data-i18n="landing.missions_title">{{ __('landing.missions_title') }}</h2>
        <p class="rh-text-muted" data-i18n="landing.missions_body">{{ __('landing.missions_body') }}</p>
        <div class="rh-missions">
            @php
                $missionRows = [
                    ['cat' => 'missions_cat_body', 'task' => 'missions_d1_body'],
                    ['cat' => 'missions_cat_mind', 'task' => 'missions_d1_mind'],
                    ['cat' => 'missions_cat_discipline', 'task' => 'missions_d1_discipline'],
                    ['cat' => 'missions_cat_identity', 'task' => 'missions_d1_identity'],
                    ['cat' => 'missions_cat_education', 'task' => 'missions_d1_education'],
                ];
            @endphp
            @foreach ($missionRows as $row)
                <div class="rh-mission">
                    <span class="rh-mission__cat" data-i18n="landing.{{ $row['cat'] }}">{{ __("landing.{$row['cat']}") }}</span>
                    <p data-i18n="landing.{{ $row['task'] }}">{{ __("landing.{$row['task']}") }}</p>
                </div>
            @endforeach
        </div>
        <button type="button" class="rh-btn rh-btn--secondary" wire:click="startCheckIn">
            <span data-i18n="landing.missions_cta">{{ __('landing.missions_cta') }}</span>
        </button>
    </section>

    <section class="rh-section rh-section--compact" id="roadmap">
        <h2 class="rh-heading" data-i18n="landing.roadmap_title">{{ __('landing.roadmap_title') }}</h2>
        <div class="rh-roadmap">
            @foreach (range(1, 5) as $p)
                <article class="rh-card rh-card--phase">
                    <span class="rh-card__phase" data-i18n="landing.roadmap_p{{ $p }}_days">{{ __("landing.roadmap_p{$p}_days") }}</span>
                    <h3 data-i18n="landing.roadmap_p{{ $p }}_title">{{ __("landing.roadmap_p{$p}_title") }}</h3>
                    <p data-i18n="landing.roadmap_p{{ $p }}_desc">{{ __("landing.roadmap_p{$p}_desc") }}</p>
                </article>
            @endforeach
        </div>
        <button type="button" class="rh-btn rh-btn--secondary" wire:click="startCheckIn">
            <span data-i18n="landing.roadmap_cta">{{ __('landing.roadmap_cta') }}</span>
        </button>
    </section>

    <section class="rh-section rh-section--compact" id="premium">
        <h2 class="rh-heading" data-i18n="landing.premium_title">{{ __('landing.premium_title') }}</h2>
        <p class="rh-caption mb-4" data-i18n="landing.premium_note">{{ __('landing.premium_note') }}</p>
        <div class="rh-pricing">
            <div class="rh-card">
                <h3 data-i18n="landing.premium_free">{{ __('landing.premium_free') }}</h3>
                <ul class="rh-list">
                    @foreach (range(1, 5) as $i)
                        <li data-i18n="landing.premium_free_{{ $i }}">{{ __("landing.premium_free_{$i}") }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="rh-card rh-card--highlight">
                <h3 data-i18n="landing.premium_pro">{{ __('landing.premium_pro') }}</h3>
                <ul class="rh-list">
                    @foreach (range(1, 7) as $i)
                        <li data-i18n="landing.premium_pro_{{ $i }}">{{ __("landing.premium_pro_{$i}") }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <a href="{{ route('pricing', ['locale' => app()->getLocale()]) }}" class="rh-btn rh-btn--secondary" wire:navigate>
            <span data-i18n="landing.premium_cta">{{ __('landing.premium_cta') }}</span>
        </a>
    </section>

    <section class="rh-section rh-cta-band" id="start">
        <h2 class="rh-heading rh-heading--center" data-i18n="landing.final_title">{{ __('landing.final_title') }}</h2>
        <p class="rh-text-muted rh-text-muted--center" data-i18n="landing.final_subtitle">{{ __('landing.final_subtitle') }}</p>
        <div class="rh-actions rh-actions--center">
            <button type="button" class="rh-btn rh-btn--primary rh-btn--lg" wire:click="startCheckIn">
                <span data-i18n="landing.cta_step1">{{ __('landing.cta_step1') }}</span>
            </button>
            <a href="#emergency" class="rh-btn rh-btn--warn rh-btn--lg">
                <span data-i18n="landing.cta_almost_text">{{ __('landing.cta_almost_text') }}</span>
            </a>
        </div>
    </section>

    <footer class="rh-footer">
        <p data-i18n="landing.footer_for">{{ __('landing.footer_for') }}</p>
        @include('partials.language-switcher')
    </footer>
</div>
