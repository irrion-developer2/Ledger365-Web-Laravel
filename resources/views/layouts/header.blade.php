@php
    $users = \Auth::user();
@endphp
<header class="dash-header transprent-bg">
    <div class="header-wrapper">
        <div class="me-auto dash-mob-drp">
            <ul class="list-unstyled">
                <li class="dash-h-item mob-hamburger">
                    <a href="#!" class="dash-head-link" id="mobile-collapse">
                        <div class="hamburger hamburger--arrowturn">
                            <div class="hamburger-box">
                                <div class="hamburger-inner"></div>
                            </div>
                        </div>
                    </a>
                </li>
                <li class="dropdown dash-h-item drp-company">
                    <a class="dash-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown"
                        href="javascript:void(0);" role="button" aria-haspopup="false" aria-expanded="false">
                        <span>
                            <img src="{{ url('assets/images/avatar/avatar.png') }}" class="rounded-circle mr-1">
                        </span>
                        <span class="hide-mob ms-2">{{ __('Hi,') }} {{ Auth::user()->name }}</span>
                        <i class="ti ti-chevron-down drp-arrow nocolor hide-mob"></i>
                    </a>
                    <div class="dropdown-menu dash-h-dropdown">
                        <a href="{{ route('central.profile.show') }}" class="dropdown-item">
                            <i class="ti ti-user"></i>
                            <span>{{ __('Profile') }}</span>
                        </a>
                        <a href="javascript:void(0)" class="dropdown-item"
                            onclick="document.getElementById('logout-form').submit()">
                            <i class="ti ti-power"></i>
                            <span>{{ __('Logout') }}</span>
                            <form action="{{ route('logout') }}" method="POST" id="logout-form"> @csrf </form>
                        </a>
                    </div>
                </li>
            </ul>
        </div>
        <div class="ms-auto">
            <ul class="list-unstyled">
           
            </ul>
        </div>
    </div>
</header>