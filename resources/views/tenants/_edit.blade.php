@extends("layouts.main")

@section('title', __('Edit Tenant | PreciseCA'))

@section("wrapper")
<div class="page-wrapper">
    <div class="page-content">
        <!--breadcrumb-->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Tenant</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit New User</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto">
                <div class="btn-group">
                    <button type="button" class="btn btn-primary" onclick="window.history.back();">Back</button>
                </div>
            </div>
        </div>
        <!--end breadcrumb-->
        <div class="row">
            <div class="card">
                <div class="card-header px-4 py-3">
                    <h5 class="mb-0">Edit User</h5>
                </div>
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                <div class="card-body p-4">
                    <form class="row g-3 needs-validation" novalidate method="Put" action="{{ route('tenants.update', $tenant->id) }}">
                        @csrf
                        <div class="col-md-6">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $tenant->name) }}" placeholder="Enter Name" required>
                            <div class="valid-feedback">Looks good!</div>
                            <div class="invalid-feedback">Please provide a name.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="domain_name" class="form-label">Domain Name</label>
                            <input type="text" class="form-control" id="domain_name" name="domain_name" value="{{ old('domain_name', $tenant->domain_name) }}" placeholder="Enter Domain Name" required>
                            <div class="valid-feedback">Looks good!</div>
                            <div class="invalid-feedback">Please provide a domain name.</div>
                        </div>
                        <div class="col-md-12">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $tenant->email) }}" placeholder="Enter Email" required>
                            <div class="valid-feedback">Looks good!</div>
                            <div class="invalid-feedback">Please provide a valid email.</div>
                        </div>
                        <div class="col-md-12">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <div class="valid-feedback">Looks good!</div>
                            <div class="invalid-feedback">Please choose a password.</div>
                        </div>
                        <div class="col-md-12">
                            <label for="password_confirmation" class="form-label">Password Confirmation</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Password Confirmation" required>
                            <div class="valid-feedback">Looks good!</div>
                            <div class="invalid-feedback">Please confirm your password.</div>
                        </div>
                        <div class="col-md-12 text-end d-grid">
                            <div class="gap-3">
                                <button type="submit" class="btn btn-primary px-4">Submit</button>
                                {{-- <button type="reset" class="btn btn-light px-4">Reset</button> --}}
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!--end row-->
    </div>
</div>
@endsection

@section("script")
<script>
    // Example starter JavaScript for disabling form submissions if there are invalid fields
    (function () {
        'use strict'

        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.querySelectorAll('.needs-validation')

        // Loop over them and prevent submission
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }

                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>
@endsection
