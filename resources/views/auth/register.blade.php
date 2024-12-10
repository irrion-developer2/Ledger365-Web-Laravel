<x-guest-layout>
    <x-jet-authentication-card>
        <x-slot name="logo">
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
                                            <img src="assets/images/precise/imageedit_4_4313936362.png" width="60" alt="" />
                                        </div>
                                        <div class="text-center mb-4">
                                            <h5 class="">Sign Up</h5>
                                            <p class="mb-0">Please fill the below details to create your account</p>
                                        </div>
                                        <x-jet-validation-errors class="mb-4 text-danger" />
                                        <div class="form-body">
                                            <form class="row g-3" method="POST" action="{{ route('register.submit') }}">
                                                @csrf
                                                <input type="hidden" class="form-control" name="role" value="Owner">
                                            
                                                <div class="col-12">
                                                    <label for="inputUsername" class="form-label">Username</label>
                                                    <input type="text" name="name" value="{{ old('name') }}" required class="form-control" id="inputUsername" placeholder="Jhon">
                                                </div>
                                                <div class="col-12">
                                                    <label for="inputEmailAddress" class="form-label">Email Address</label>
                                                    <input type="email" class="form-control" id="inputEmailAddress" placeholder="example@user.com"  name="email" value="{{ old('email') }}" required>
                                                </div>
                                                <div class="col-12">
                                                    <label for="inputMobileNumber" class="form-label">Mobile Number</label>
                                                    <input type="number" name="phone" value="{{ old('phone') }}" required class="form-control" id="inputMobileNumber" placeholder="Enter Your Mobile Number">
                                                </div>
                                                <div class="col-12">
                                                    <label for="inputTallyConnectorId" class="form-label">Tally Connector Id</label>
                                                    <input type="text" class="form-control" id="inputTallyConnectorId" placeholder="Enter Tally Connector Id"  name="tally_connector_id" value="{{ old('tally_connector_id') }}">
                                                </div>
                                                <div class="col-12">
                                                    <div class="d-grid">
                                                        <button type="submit" class="btn btn-primary">Sign up</button>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="text-center ">
                                                        <p class="mb-0">Already have an account? <a href="{{ route('login') }}">Sign in here</a></p>
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
