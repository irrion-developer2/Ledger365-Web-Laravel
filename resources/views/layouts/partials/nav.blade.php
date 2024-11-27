<?php
    $user = auth()->user();
    $userId = $user->id;

    $userCompanyMappings = App\Models\UserCompanyMapping::where('user_id', $userId)->pluck('company_id')->toArray();

    $companies = App\Models\TallyCompany::whereIn('company_id', $userCompanyMappings)->get();

    $selectedCompanyIds = session('selected_company_ids', []);
?>
<style>
  .dropdown-item.selected {
      background-color: #007bff;
      color: #fff;
  }
</style>

<div class="primary-menu">
  <nav class="navbar navbar-expand-lg align-items-center">
     <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
       <div class="offcanvas-header border-bottom">
           <div class="d-flex align-items-center">
               <div class="">
                  <img src="{{ asset('assets/images/precise/preciseCA-logo.png') }}" class="logo-icon" alt="logo icon" style="width: 220px;">
               </div>
               {{-- <div class="">
                   <h4 class="logo-text">Rocker</h4>
               </div> --}}
           </div>
         <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
       </div>
       <div class="offcanvas-body">
         <ul class="navbar-nav align-items-center flex-grow-1">

          @if(auth()->check() && auth()->user()->role == 'Administrative')
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle dropdown-toggle-nocaret {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <div class="parent-icon"><i class='bx bx-home-alt'></i>
                </div>
                <div class="menu-title d-flex align-items-center">Dashboard</div>
                {{-- <div class="ms-auto dropy-icon"><i class='bx bx-chevron-down'></i></div> --}}
            </a>
          </li>
          @endif

          @if(auth()->check() && (auth()->user()->role == 'Owner' || auth()->user()->role == 'Employee'))
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle dropdown-toggle-nocaret {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <div class="parent-icon"><i class='bx bx-home-alt'></i>
                </div>
                <div class="menu-title d-flex align-items-center">Dashboard</div>
            </a>
          </li>
          @endif

            @if(auth()->check() && auth()->user()->status == 'Active' && (auth()->user()->role == 'Owner' || auth()->user()->role == 'Employee'))
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle dropdown-toggle-nocaret {{ request()->routeIs('customers.index') ? 'active' : '' }}" href="{{ route('customers.index') }}">
                  <div class="parent-icon"><i class='bx bx-group'></i>
                  </div>
                  <div class="menu-title d-flex align-items-center">Customers</div>
                  {{-- <div class="ms-auto dropy-icon"><i class='bx bx-chevron-down'></i></div> --}}
              </a>
            </li>
            @endif


            @if(auth()->check() && auth()->user()->status == 'Active' && (auth()->user()->role == 'Owner' || auth()->user()->role == 'Employee'))
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle dropdown-toggle-nocaret {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
                  <div class="parent-icon"><i class='bx bx-cube'></i>
                  </div>
                  <div class="menu-title d-flex align-items-center">Suppliers</div>
                  {{-- <div class="ms-auto dropy-icon"><i class='bx bx-chevron-down'></i></div> --}}
              </a>
            </li>
            @endif

            @if(auth()->check() && auth()->user()->status == 'Active' && (auth()->user()->role == 'Owner' || auth()->user()->role == 'Employee'))
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle dropdown-toggle-nocaret {{ request()->routeIs('stock-items.*') ? 'active' : '' }}" href="{{ route('stock-items.index') }}">
                  <div class="parent-icon"><i class='bx bx-box'></i>
                  </div>
                  <div class="menu-title d-flex align-items-center">Stock Items</div>
                  {{-- <div class="ms-auto dropy-icon"><i class='bx bx-chevron-down'></i></div> --}}
              </a>
            </li>
            @endif

            @if(auth()->check() && auth()->user()->status == 'Active' && (auth()->user()->role == 'Owner' || auth()->user()->role == 'Employee'))
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle dropdown-toggle-nocaret {{ request()->routeIs('sales.*') ? 'active' : '' }}" href="{{ route('sales.index') }}">
                  <div class="parent-icon"><i class='lni lni-stats-up'></i>
                  </div>
                  <div class="menu-title d-flex align-items-center">Sales</div>
                  {{-- <div class="ms-auto dropy-icon"><i class='bx bx-chevron-down'></i></div> --}}
              </a>
            </li>
            @endif

            @if(auth()->check() && auth()->user()->status == 'Active' && (auth()->user()->role == 'Owner' || auth()->user()->role == 'Employee'))
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle dropdown-toggle-nocaret {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                  <div class="parent-icon"><i class='bx bx-calculator'></i>
                  </div>
                  <div class="menu-title d-flex align-items-center">Reports</div>
                  {{-- <div class="ms-auto dropy-icon"><i class='bx bx-chevron-down'></i></div> --}}
              </a>
            </li>
            @endif

            @if(auth()->check() && auth()->user()->status == 'Active' && auth()->user()->role == 'Administrative')
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle dropdown-toggle-nocaret {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                  <div class="parent-icon"><i class='bx bx-building'></i><i class='bx bx-group'></i>
                  </div>
                  <div class="menu-title d-flex align-items-center">Users</div>
                  {{-- <div class="ms-auto dropy-icon"><i class='bx bx-chevron-down'></i></div> --}}
              </a>
            </li>
            @endif

            
            {{--  @if(auth()->check() && auth()->user()->status == 'Active' && auth()->user()->role == 'Owner')
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle dropdown-toggle-nocaret {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">
                  <div class="parent-icon"><i class='bx bx-building'></i><i class='bx bx-group'></i>
                  </div>
                  <div class="menu-title d-flex align-items-center">Employee</div>
              </a>
            </li>
            @endif  --}}

            <li class="nav-item dropdown d-none">
               <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="javascript:;" data-bs-toggle="dropdown">
                   <div class="parent-icon"><i class='bx bx-briefcase-alt'></i>
                   </div>
                   <div class="menu-title d-flex align-items-center">UI Elements</div>
                   <div class="ms-auto dropy-icon"><i class='bx bx-chevron-down'></i></div>
               </a>
               <ul class="dropdown-menu">
                 <li class="nav-item dropend">
                   <a class="dropdown-item dropdown-toggle dropdown-toggle-nocaret" href="javascript:;"><i class='bx bx-ghost'></i>Components</a>
                   <ul class="dropdown-menu scroll-menu">
                       {{-- <li><a class="dropdown-item" href="{{ url('component-navbar') }}"><i class='bx bx-radio-circle'></i>Navbar</a></li> --}}
                    </ul>
                 </li>
               </ul>
            </li>

            @if(auth()->check() && auth()->user()->status == 'Active' && (auth()->user()->role == 'Owner' || auth()->user()->role == 'Employee'))
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle dropdown-toggle-nocaret {{ request()->routeIs('analytics.*') ? 'active' : '' }}" href="{{ route('analytics.index') }}">
                  <div class="parent-icon"><i class='bx bxs-analyse'></i>
                  </div>
                  <div class="menu-title d-flex align-items-center">Analytics</div>
              </a>
            </li>
            @endif

            @if(auth()->check() && auth()->user()->status == 'Active' && (auth()->user()->role == 'Owner' || auth()->user()->role == 'Employee'))
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="javascript:;" data-bs-toggle="dropdown">
                    <div class="parent-icon"><i class="bx bx-buildings"></i></div>
                    <div class="menu-title d-flex align-items-center company-changes">
                        {{ ('Select Companies') }}
                    </div>
                    <div class="ms-auto dropy-icon"><i class="bx bx-chevron-down"></i></div>
                </a>
                <ul class="dropdown-menu">
                    @foreach($companies as $company)
                        @php
                            $isChecked = in_array($company->company_id, $selectedCompanyIds);
                        @endphp
                        <li>
                            <label class="dropdown-item">
                                {{ $company->company_name }}
                                <input type="checkbox" class="company-checkbox ms-2" value="{{ $company->company_id }}"
                                    data-name="{{ $company->company_name }}"
                                    onchange="updateSelectedCompanies()"
                                    {{ $isChecked ? 'checked' : '' }}>
                            </label>
                        </li>
                    @endforeach
                    <li>
                        <button class="btn btn-primary w-100 mt-2" onclick="changeCompanies()">Confirm Selection</button>
                    </li>
                </ul>
            </li>
            @endif
            
            
         </ul>
       </div>
     </div>
 </nav>
</div>
<script>
  let selectedCompanies = [];

  function updateSelectedCompanies() {
      selectedCompanies = [];
      document.querySelectorAll('.company-checkbox:checked').forEach(checkbox => {
          selectedCompanies.push({
              id: checkbox.value,
              name: checkbox.getAttribute('data-name')
          });
      });

      const selectedNames = selectedCompanies.map(company => company.name).join(', ');
      document.querySelector('.company-changes').textContent = selectedNames || 'Select Companies';
  }

  function changeCompanies() {
      if (selectedCompanies.length === 0) {
          alert("Please select at least one company.");
          return;
      }

      const companyIds = selectedCompanies.map(company => company.id);
      const companyNames = selectedCompanies.map(company => company.name).join(', ');

      fetch('/set-company-session', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
              company_ids: companyIds,
              company_names: companyNames
          })
      })
      .then(response => {
          if (!response.ok) {
              return response.text().then(text => { throw new Error(text) });
          }
          return response.json();
      })
      .then(sessionData => {
          if (sessionData.success) {
              window.location.reload();
          } else {
              console.warn('Failed to update session.');
          }
      })
      .catch(error => {
          console.error('Error:', error.message);
      });
  }
</script>
