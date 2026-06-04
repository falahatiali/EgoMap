<nav class="eg-admin-subnav mb-4" aria-label="{{ __('admin.gamification.nav_label') }}">
    <a href="{{ route('admin.gamification.catalog') }}" @class(['eg-admin-subnav__link', 'is-active' => ($activeGamificationNav ?? '') === 'catalog'])>{{ __('admin.gamification.nav_catalog') }}</a>
    <a href="{{ route('admin.gamification.rules.index') }}" @class(['eg-admin-subnav__link', 'is-active' => ($activeGamificationNav ?? '') === 'rules'])>{{ __('admin.gamification.nav_rules') }}</a>
    <a href="{{ route('admin.gamification.badges.index') }}" @class(['eg-admin-subnav__link', 'is-active' => ($activeGamificationNav ?? '') === 'badges'])>{{ __('admin.gamification.nav_badges') }}</a>
    <a href="{{ route('admin.gamification.perks.index') }}" @class(['eg-admin-subnav__link', 'is-active' => ($activeGamificationNav ?? '') === 'perks'])>{{ __('admin.gamification.nav_perks') }}</a>
    <a href="{{ route('admin.gamification.punishments.index') }}" @class(['eg-admin-subnav__link', 'is-active' => ($activeGamificationNav ?? '') === 'punishments'])>{{ __('admin.gamification.nav_punishments') }}</a>
    <a href="{{ route('admin.gamification.shop.index') }}" @class(['eg-admin-subnav__link', 'is-active' => ($activeGamificationNav ?? '') === 'shop'])>{{ __('admin.gamification.nav_shop') }}</a>
    <a href="{{ route('admin.gamification.transactions.index') }}" @class(['eg-admin-subnav__link', 'is-active' => ($activeGamificationNav ?? '') === 'transactions'])>{{ __('admin.gamification.nav_transactions') }}</a>
    <a href="{{ route('admin.gamification.analytics') }}" @class(['eg-admin-subnav__link', 'is-active' => ($activeGamificationNav ?? '') === 'analytics'])>{{ __('admin.gamification.nav_analytics') }}</a>
    <a href="{{ route('admin.gamification.simulator') }}" @class(['eg-admin-subnav__link', 'is-active' => ($activeGamificationNav ?? '') === 'simulator'])>{{ __('admin.gamification.nav_simulator') }}</a>
    <a href="{{ route('admin.gamification.wallets.index') }}" @class(['eg-admin-subnav__link', 'is-active' => ($activeGamificationNav ?? '') === 'wallets'])>{{ __('admin.gamification.nav_wallets') }}</a>
</nav>
