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
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/searchbuilder/1.3.0/css/searchBuilder.dataTables.min.css">



        <!-- Theme Style CSS -->
        <link rel="stylesheet" href="{{ url('assets/css/dark-theme.css') }}" />
        <link rel="stylesheet" href="{{ url('assets/css/semi-dark.css') }}" />
        <link rel="stylesheet" href="{{ url('assets/css/header-colors.css') }}" />

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
        {{ $slot }}
        @livewireScripts
		<!--end page wrapper -->
        <!-- Search Modal -->
        @include("layouts.partials.search-modal")
        <!-- End Search Model -->
		<!--start overlay-->
		<div class="overlay toggle-icon"></div>
		<!--end overlay-->
		<!--Start Back To Top Button--> <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
		<!--End Back To Top Button-->
		<footer class="page-footer">
			<p class="mb-0">Copyright © {{ date("Y") }}. All right reserved.</p>
		</footer>
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


    <script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.flash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.colVis.min.js"></script>
    <script src="https://cdn.datatables.net/searchbuilder/1.3.0/js/dataTables.searchBuilder.min.js"></script>


	@yield("script")
    @stack('javascript')
    @include('layouts.includes.alerts')

    </body>
</html>
