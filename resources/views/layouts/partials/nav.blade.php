<?php
    $user = auth()->user();
    $userSubIds = json_decode($user->sub_id, true);

    if (!is_array($userSubIds)) {
        $userSubIds = [$user->sub_id];
    }
    $companies = App\Models\TallyCompany::whereIn('sub_id', $userSubIds)->get();
?>
<style>
  .dropdown-item.selected {
      background-color: #007bff; /* Example color */
      color: #fff; /* Example text color */
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

            {{-- <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="javascript:;" data-bs-toggle="dropdown">
                  <div class="parent-icon"><i class='bx bx-cube'></i>
                  </div>
                  <div class="menu-title d-flex align-items-center">Apps & Pages</div>
                  <div class="ms-auto dropy-icon"><i class='bx bx-chevron-down'></i></div>
              </a>
              <ul class="dropdown-menu">
                <li class="nav-item dropend">
                  <a class="dropdown-item dropdown-toggle dropdown-toggle-nocaret" href="javascript:;"><i class='bx bx-file'></i>Tenants</a>
                  <ul class="dropdown-menu submenu">
                      <li><a class="dropdown-item" href="{{ route('tenants.create') }}"><i class='bx bx-radio-circle'></i>Create Tenant</a></li>
                      <li><a class="dropdown-item" href="{{ route('tenants.index') }}"><i class='bx bx-radio-circle'></i>Tenant list</a></li>
                    </ul>
                </li>
              </ul>
            </li> --}}

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

            {{-- @if(auth()->check() && auth()->user()->status == 'Active' && (auth()->user()->role == 'Owner' || auth()->user()->role == 'Employee'))
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle dropdown-toggle-nocaret {{ request()->routeIs('otherLedgers.index') ? 'active' : '' }}" href="{{ route('otherLedgers.index') }}">
                  <div class="parent-icon"><i class='bx bx-group'></i>
                  </div>
                  <div class="menu-title d-flex align-items-center">Other Ledgers</div>
              </a>
            </li>
            @endif --}}


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

            
            @if(auth()->check() && auth()->user()->status == 'Active' && auth()->user()->role == 'Owner')
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle dropdown-toggle-nocaret {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">
                  <div class="parent-icon"><i class='bx bx-building'></i><i class='bx bx-group'></i>
                  </div>
                  <div class="menu-title d-flex align-items-center">Employee</div>
              </a>
            </li>
            @endif

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
                  <div class="parent-icon"><i class="bx bx-buildings"></i>
                  </div>
                  <div class="menu-title d-flex align-items-center company-changes">Companies</div>
                  <div class="ms-auto dropy-icon"><i class="bx bx-chevron-down"></i></div>
                </a>
                <ul class="dropdown-menu">
                  @foreach($companies as $company)
                      <li>
                          <a class="dropdown-item {{ session('selected_company_id') == $company->id ? 'selected' : '' }}"
                            href="javascript:;" onclick="changeCompany('{{ $company->id }}', '{{ $company->name }}')">
                              {{ $company->name }}
                          </a>
                      </li>
                  @endforeach
                </ul>
            </li>
            @endif
            
         </ul>
       </div>
     </div>
 </nav>
</div>
<script>
  function changeCompany(companyId, companyName) {
      console.log('Selected Company ID:', companyId);
      if (!companyId) {
          return;
      }
      const url = `/fetch-company-data/${companyId}`; 
      console.log('Request URL:', url);
  
      fetch(url)
          .then(response => {
              if (!response.ok) {
                  return Promise.reject(new Error('Network response was not ok: ' + response.statusText));
              }
              return response.json();
          })
          .then(data => {
              if (data && data.company) { 
                  document.querySelector('.company-changes').textContent = data.company.name;
                  localStorage.setItem('selectedCompanyId', companyId);
  
                  return fetch('/set-company-session', {
                      method: 'POST',
                      headers: {
                          'Content-Type': 'application/json',
                          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                      },
                      body: JSON.stringify({
                          company_id: companyId,
                          company_name: companyName
                      }),
                  });
              } else {
                  console.warn('Company data not found'); 
                  return Promise.reject(new Error('Company data not found'));
              }
          })
          .then(response => {
              if (!response.ok) {
                  return Promise.reject(new Error('Failed to update session: ' + response.statusText));
              }
              return response.json();
          })
          .then(sessionData => {
              if (sessionData.success) {
                  console.log('Session updated with company:', sessionData.company);
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

{{-- <script>
  function changeCompany(companyId, companyName) {
      console.log('Selected Company ID:', companyId);
      if (!companyId) {
          return;
      }
      const url = `/fetch-company-data/${companyId}`; 
      console.log('Request URL:', url);
  
      fetch(url)
          .then(response => {
              if (!response.ok) {
                  return Promise.reject(new Error('Network response was not ok: ' + response.statusText));
              }
              return response.json();
          })
          .then(data => {
              if (data && data.company) { 
                  document.querySelector('.company-changes').textContent = data.company.name;
                  localStorage.setItem('selectedCompanyId', companyId);
  
                  return fetch('/set-company-session', {
                      method: 'POST',
                      headers: {
                          'Content-Type': 'application/json',
                          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                      },
                      body: JSON.stringify({
                          company_id: companyId,
                          company_name: companyName
                      }),
                  });
              } else {
                  console.warn('Company data not found'); 
                  return Promise.reject(new Error('Company data not found'));
              }
          })
          .then(response => {
              if (!response.ok) {
                  return Promise.reject(new Error('Failed to update session: ' + response.statusText));
              }
              return response.json();
          })
          .then(sessionData => {
              if (sessionData.success) {
                  console.log('Session updated with company:', sessionData.company);
              } else {
                  console.warn('Failed to update session.');
              }
          })
          .catch(error => {
              console.error('Error:', error.message);
          });
  
      // Update the URL with the selected company ID
      // if (window.location.href.indexOf(`companyData=${companyId}`) === -1) {
      //     window.location.search = `?companyData=${companyId}`;
      // }
  }
</script> --}}