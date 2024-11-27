<x-guest-layout>
    <x-jet-authentication-card>
        <x-slot name="logo">
            {{-- <x-jet-authentication-card-logo /> --}}
        </x-slot>


        @if (session('status'))
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ session('status') }}
            </div>
        @endif

        <div class="wrapper">
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="authentication-forgot d-flex align-items-center justify-content-center">
                    <div class="card forgot-box">
                        <div class="card-body">
                            <div class="p-3">
                                <div class="text-center">
                                    <img src="assets/images/icons/forgot-2.png" width="100" alt="" />
                                </div>
                                <h4 class="mt-5 font-weight-bold">Forgot Password?</h4>
                                <p class="text-muted">Enter your registered email ID to reset the password</p>
                                
                                <x-jet-validation-errors class="mb-4 text-danger" />
                                <div class="my-4">
                                    <label class="form-label">Email id</label>
                                    <input type="email" class="form-control" name="email" :value="old('email')" required placeholder="example@user.com" />
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Send</button>
                                    <a href="{{ route('login') }}" class="btn btn-light"><i class='bx bx-arrow-back me-1'></i>Back to Login</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>



    </x-jet-authentication-card>
</x-guest-layout>
