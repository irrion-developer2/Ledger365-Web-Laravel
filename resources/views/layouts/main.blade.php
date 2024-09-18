<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'PreciseCA')</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap">

        <!-- Scripts -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!--favicon-->
        <link rel="icon" href="{{ url('assets/images/precise/imageedit_4_4313936362.png') }}" type="image/png" />
        <!--plugins-->
        @yield("style")
        <link href="{{ url('assets/plugins/simplebar/css/simplebar.css') }}" rel="stylesheet" />
        <link href="{{ url('assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet" />
        <link href="{{ url('assets/plugins/metismenu/css/metisMenu.min.css') }}" rel="stylesheet" />
        <!-- loader-->
        <link href="{{ url('assets/css/pace.min.css') }}" rel="stylesheet" />
        <script src="{{ url('assets/js/pace.min.js') }}"></script>
        <!-- Bootstrap CSS -->
        <link href="{{ url('assets/css/bootstrap.min.css') }}" rel="stylesheet">
        <link href="{{ url('assets/css/bootstrap-extended.css') }}" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
        <link href="{{ url('assets/css/app.css') }}" rel="stylesheet">
        <link href="{{ url('assets/css/icons.css') }}" rel="stylesheet">
        <link href="{{ url('assets/css/custom.css') }}" rel="stylesheet">

        

        <!-- Theme Style CSS -->
        <link rel="stylesheet" href="{{ url('assets/css/dark-theme.css') }}" />
        <link rel="stylesheet" href="{{ url('assets/css/semi-dark.css') }}" />
        <link rel="stylesheet" href="{{ url('assets/css/header-colors.css') }}" />

        <style>
            th.fixed-column {
                position: sticky !important;
                left: 0;
                z-index: 8;
                background-color: white;
            }
            td.fixed-column {
                position: sticky;
                left: 0;
                z-index: 0;
                background-color: white;
            }
        </style>
        @stack('css')
        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        	<!--wrapper-->
	<div class="wrapper">
		<!--start header -->
		@include("layouts.partials.header")
		<!--end header -->
		<!--navigation-->
		@include("layouts.partials.nav")
		<!--end navigation-->
		<!--start page wrapper -->
		@yield("wrapper")
		<!--end page wrapper -->
        <!-- Search Modal -->
        @include("layouts.partials.search-modal")
        <!-- End Search Model -->
		<!--start overlay-->
		<div class="overlay toggle-icon"></div>
		<!--end overlay-->
		<!--Start Back To Top Button--> <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
		<!--End Back To Top Button-->
        @include("layouts.footer")
		
	</div>
	<!--end wrapper-->
    
	<!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

	<script src="{{ url('assets/js/bootstrap.bundle.min.js') }}"></script>
	<!--plugins-->
	{{-- <script src="{{ asset('assets/js/jquery.min.js') }}"></script> --}}
	<script src="{{ url('assets/plugins/simplebar/js/simplebar.min.js') }}"></script>
	<script src="{{ url('assets/plugins/metismenu/js/metisMenu.min.js') }}"></script>
	<script src="{{ url('assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
	<!--app JS-->
	<script src="{{ url('assets/js/app.js') }}"></script>

    <script src="{{ url('assets/js/custom.js') }}"></script>
    <script src="{{ url('vendor/notifier/bootstrap-notify.min.js') }}"></script>
	@yield("script")
    @stack('javascript')
    @include('layouts.includes.alerts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
                const savedCompanyId = localStorage.getItem('selectedCompanyId');

                if (savedCompanyId) {
                    changeCompany(savedCompanyId);
                }
            });

    </script>
    </body>
</html>
