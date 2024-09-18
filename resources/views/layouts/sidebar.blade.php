<nav class="dash-sidebar light-sidebar">
    <div class="navbar-wrapper">
        <div class="m-headers logo-col">
            <a href="{{ route('dashboard') }}" class="b-brand">
                <!-- ========   change your logo hear   ============ -->
                    <img src="{{ url('assets/images/logo/app-dark-logo.png') }}"
                    class="footer-dark-logo">
            </a>
        </div>
        <div class="navbar-content">
            <ul class="dash-navbar">
                <li class="dash-item dash-hasmenu">
                    <a href="{{ route('dashboard') }}" class="dash-link">
                        <span class="dash-micon"><i class="ti ti-home"></i></span>
                        <span class="dash-mtext">{{ __('Dashboard') }}</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
