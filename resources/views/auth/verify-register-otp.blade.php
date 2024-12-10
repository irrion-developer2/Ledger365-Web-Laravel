<!-- resources/views/auth/verify-otp.blade.php -->

<x-guest-layout>
    <x-jet-authentication-card>
        <x-slot name="logo">
            {{-- <x-jet-authentication-card-logo /> --}}
        </x-slot>

        <div class="wrapper">
            <div class="d-flex align-items-center justify-content-center my-5">
                <div class="container-fluid">
                    <div class="row row-cols-1 row-cols-lg-2 row-cols-xl-3">
                        <div class="col mx-auto">
                            <div class="card mb-0">
                                <div class="card-body">
                                    <div class="p-4">
                                        <div class="mb-3 text-center">
                                            <img src="{{ asset('assets/images/precise/imageedit_4_4313936362.png') }}" width="60" alt="Logo" />
                                        </div>
                                        <div class="text-center mb-4">
                                            <h5 class="">Verify OTP</h5>
                                            <p class="mb-0">Enter the OTP sent to your phone number</p>
                                        </div>
                                        @if(session('phone'))
                                            <p class="text-center">Phone: {{ session('phone') }}</p>
                                        @endif
                                        @if(session('success'))
                                            <div class="alert alert-success">
                                                {{ session('success') }}
                                            </div>
                                        @endif
                                        <x-jet-validation-errors class="mb-4 text-danger" />
                                        <div class="form-body">
                                            <form class="row g-3" method="POST" action="{{ route('verify-otp.submit') }}">
                                                @csrf
                                                <div class="col-12">
                                                    <label for="inputOTP" class="form-label">OTP</label>
                                                    <input type="text" name="otp" required class="form-control" id="inputOTP" placeholder="Enter OTP">
                                                </div>
                                                <div class="col-12">
                                                    <div class="d-grid">
                                                        <button type="submit" class="btn btn-primary">Verify OTP</button>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="text-center">
                                                        <p class="mb-0">Didn&apos;t receive OTP? 
                                                            <form method="POST" action="{{ route('resend-otp') }}" style="display: inline;">
                                                                @csrf
                                                                <button type="submit" class="btn btn-link p-0 m-0 align-baseline">Resend OTP</button>
                                                            </form>
                                                        </p>
                                                    </div>
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
