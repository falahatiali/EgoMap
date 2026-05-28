@if (session('admin_notice'))
    <div @class([
        'eg-admin-notice',
        'eg-admin-notice--success' => session('admin_notice_type', 'success') === 'success',
        'eg-admin-notice--danger' => session('admin_notice_type') === 'danger',
    ])>
        <i class="fa-solid {{ session('admin_notice_type') === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-check' }}" aria-hidden="true"></i>
        <span>{{ session('admin_notice') }}</span>
    </div>
@endif
