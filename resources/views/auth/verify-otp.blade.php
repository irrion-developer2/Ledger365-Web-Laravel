<x-guest-layout>
    <x-jet-authentication-card>
        <x-slot name="logo">
            {{-- <x-jet-authentication-card-logo /> --}}
        </x-slot>

        <div class="wrapper">
            <div class="section-authentication-signin d-flex align-items-center justify-content-center my-5 my-lg-0">
                <div class="container">
                    <div class="row row-cols-1 row-cols-lg-2 row-cols-xl-3">
                        <div class="col mx-auto">
                            <div class="card mb-0">
                                <div class="card-body">
                                    <div class="p-4">
                                        <div class="mb-3 text-center">
                                            <img src="assets/images/precise/imageedit_4_4313936362.png" width="60" alt="" />
                                        </div>
                                        <div class="text-center mb-4">
                                            <h5 class="">Sign In</h5>
                                            <p class="mb-0">Please log in to your account</p>
                                        </div>

                                        <x-jet-validation-errors class="mb-4 text-danger" />
                                        @if (session('status'))
                                            <div class="mb-4 font-medium text-sm text-green-600 text-danger">
                                                {{ session('status') }}
                                            </div>
                                        @endif

                                        <div class="form-body">

                                            <form class="row g-3" method="POST" action="{{ route('verify-otp') }}">
                                                @csrf
                                                <input type="hidden" name="phone" value="{{ $phone }}" />
                                            
                                                <div class="mt-4">
                                                    <x-jet-label for="otp" value="OTP" />
                                                    <x-jet-input id="otp" class="form-control block mt-1 w-full" type="text" name="otp" required autofocus />
                                                </div>
                                            
                                                <div class="flex items-center justify-end mt-4">
                                                    <x-jet-button class="btn btn-primary ml-4">
                                                        {{ __('Verify OTP') }}
                                                    </x-jet-button>
                                                </div>
                                            </form>
                                            

                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end row-->
                </div>
            </div>
        </div>
    </x-jet-authentication-card>
</x-guest-layout>
