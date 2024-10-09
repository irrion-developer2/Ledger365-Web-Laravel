@extends("layouts.main")
@section('title', __('Add Employee | PreciseCA'))
@section("wrapper")
    <div class="page-wrapper">
    <div class="page-content">
        <!--breadcrumb-->
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Add Employee</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Add Employee</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!--end breadcrumb-->
      
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-body p-4">
                        <h5 class="mb-4">Add Employee Details</h5>
                        <form action="{{ route('employees.save') }}" method="POST">
                            @csrf 
                            <input type="hidden" name="owner_employee_id" class="form-control" value="{{ auth()->user()->id }}">
                            <input type="hidden" name="role" class="form-control" value="Employee">

                            <div class="row mb-3 gap-4">
                                <div class="col-12">
                                    <label for="inputUsername" class="form-label">Username</label>
                                    <input type="text" name="name" :value="old('name')" required class="form-control" id="inputUsername" placeholder="Jhon">
                                </div>
                                <div class="col-12">
                                    <label for="inputEmailAddress" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="inputEmailAddress" placeholder="example@user.com"  name="email" :value="old('email')" required>
                                </div>
                                <div class="col-12">
                                    <label for="inputMobileNumber" class="form-label">Mobile Number</label>
                                    <input type="number" name="phone" :value="old('phone')" required class="form-control" id="inputMobileNumber" placeholder="Enter Your Monile Number">
                                </div>
                                <div class="col-12">
                                    <label for="inputTallyConnectorId" class="form-label">Tally Connector Id</label>
                                    <input type="text" class="form-control" id="inputTallyConnectorId" placeholder="Enter Tally Connector Id"  name="tally_connector_id" :value="old('tally_connector_id')">
                                </div>
                                {{-- <div class="col-12">
                                    <label for="inputRole" class="form-label">Role</label>
                                    <select class="form-select mb-3" aria-label="Default select example" name="role">
                                        <option selected="">Select Role</option>
                                        <option value="Administrative">Super Admin</option>
                                        <option value="Owner">Company Owner</option>
                                    </select>
                                </div> --}}
                              
                            </div>
                            
                            <div class="row mt-4">
                                <label class="col-sm-3 col-form-label"></label>
                                <div class="col-sm-9">
                                    <div class="d-md-flex d-grid align-items-center gap-3">
                                        <button type="submit" class="btn btn-primary px-4">Submit</button>
                                        <button type="button" class="btn btn-light px-4" onclick="history.back();">Back</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
@push('javascript')
   
@endpush