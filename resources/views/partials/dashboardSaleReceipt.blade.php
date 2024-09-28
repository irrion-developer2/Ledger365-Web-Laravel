
<div class="row">



    <div class="col-12 col-lg-8 d-flex">
       <div class="card radius-10 w-100">
         <div class="card-header">
             <div class="d-flex align-items-center">
                 <div>
                     <h6 class="mb-0">Sales & Receipt</h6>
                 </div>
                 {{-- <div class="dropdown ms-auto">
                     <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class='bx bx-dots-horizontal-rounded font-22 text-option'></i>
                     </a>
                     <ul class="dropdown-menu">
                         <li><a class="dropdown-item" href="javascript:;">This Month</a></li>
                         <li><a class="dropdown-item" href="javascript:;">Last Month</a></li>
                         <li><a class="dropdown-item" href="javascript:;">This Quarter</a></li>
                         <li><a class="dropdown-item" href="javascript:;">Prev Quarter</a></li>
                         <li><a class="dropdown-item" href="javascript:;">This Year</a></li>
                         <li><a class="dropdown-item" href="javascript:;">Prev Year</a></li>
                     </ul>
                 </div> --}}
                 <div class="dropdown ms-auto">
                    <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown">
                      <i class='bx bx-dots-horizontal-rounded font-22 text-option'></i>
                    </a>
                    <ul class="dropdown-menu">
                      <li><a class="dropdown-item filter-option" href="javascript:;" data-filter="this_month">This Month</a></li>
                      <li><a class="dropdown-item filter-option" href="javascript:;" data-filter="last_month">Last Month</a></li>
                      <li><a class="dropdown-item filter-option" href="javascript:;" data-filter="this_quarter">This Quarter</a></li>
                      <li><a class="dropdown-item filter-option" href="javascript:;" data-filter="prev_quarter">Prev Quarter</a></li>
                      <li><a class="dropdown-item filter-option" href="javascript:;" data-filter="this_year">This Year</a></li>
                      <li><a class="dropdown-item filter-option" href="javascript:;" data-filter="prev_year">Prev Year</a></li>
                    </ul>
                </div>

             </div>
         </div>
           <div class="card-body">
             <div class="d-flex align-items-center ms-auto font-13 gap-2 mb-3">
                 <span class="border px-1 rounded cursor-pointer"><i class="bx bxs-circle me-1" style="color: #14abef" ></i>Sales &#8377 {{ number_format(abs($chartSaleAmt), 2) }}</span>
                 <span class="border px-1 rounded cursor-pointer"><i class="bx bxs-circle me-1" style="color: #ffc107"></i>Receipt &#8377 {{ number_format(abs($chartReceiptAmt), 2) }}</span>
             </div>
             <div class="chart-container-1">
                 <canvas id="salereceiptchart"></canvas>
                 <canvas id="chart1" class="d-none"></canvas>
               </div>
           </div>
           <div class="row row-cols-1 row-cols-md-2 row-cols-xl-2 g-0 row-group text-center border-top">
             <div class="col">
               <div class="p-3">
                 <h5 class="mb-0">&#8377 {{ number_format(abs($lastMonthsTotal['sales']), 2) }}</h5>
                 <small class="mb-0">Sales this Month</small>
               </div>
             </div>
             <div class="col">
               <div class="p-3">
                 <h5 class="mb-0">&#8377 {{ number_format(abs($lastMonthsTotal['receipts']), 2) }}</h5>
                 <small class="mb-0">Receipt this Month</small>
               </div>
             </div>
           </div>
       </div>
    </div>


    <div class="col-12 col-lg-4 d-flex">
        <div class="card radius-10 w-100">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div>
                        <h6 class="mb-0">Receivables</h6>
                    </div>
                    {{-- <div class="dropdown ms-auto">
                        <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown">
                            <i class='bx bx-dots-horizontal-rounded font-22 text-option'></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:;">Action</a></li>
                            <li><a class="dropdown-item" href="javascript:;">Another action</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="javascript:;">Something else here</a></li>
                        </ul>
                    </div> --}}
                </div>
            </div>
            <div class="card-body">
                <p>Total Overdue : {{ number_format(abs($pieChartDataTotal), 2) }}</p>
                <div class="chart-container-2 d-flex justify-content-center">
                    <canvas id="pieChart"></canvas>
                    <canvas id="chart2" class="d-none"></canvas>
                </div>
            </div>
            <ul id="badge-list" class="list-group list-group-flush">
                <!-- Badges will be generated dynamically here -->
            </ul>
        </div>
    </div>



    {{-- <div class="col-12 col-lg-4 d-flex">
        <div class="card radius-10 w-100">
         <div class="card-header">
             <div class="d-flex align-items-center">
                 <div>
                     <h6 class="mb-0">Receivables</h6>
                 </div>
                 <div class="dropdown ms-auto">
                     <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class='bx bx-dots-horizontal-rounded font-22 text-option'></i>
                     </a>
                     <ul class="dropdown-menu">
                         <li><a class="dropdown-item" href="javascript:;">Action</a>
                         </li>
                         <li><a class="dropdown-item" href="javascript:;">Another action</a>
                         </li>
                         <li>
                             <hr class="dropdown-divider">
                         </li>
                         <li><a class="dropdown-item" href="javascript:;">Something else here</a>
                         </li>
                     </ul>
                 </div>
             </div>
         </div>
            <div class="card-body">
             <div class="chart-container-2">
                <canvas id="pieChart"></canvas>
                 <canvas id="chart2" class="d-none"></canvas>
               </div>
            </div>
            <ul class="list-group list-group-flush">
             <li class="list-group-item d-flex bg-transparent justify-content-between align-items-center border-top"><span class="badge bg-success rounded-pill">25</span> 0 - 45 Days
             </li>
             <li class="list-group-item d-flex bg-transparent justify-content-between align-items-center"><span class="badge bg-danger rounded-pill">10</span> 45 - 90 Days
             </li>
             <li class="list-group-item d-flex bg-transparent justify-content-between align-items-center"><span class="badge bg-primary rounded-pill">65</span> 90 - 135 Days
             </li>
             <li class="list-group-item d-flex bg-transparent justify-content-between align-items-center"><span class="badge bg-warning text-dark rounded-pill">14</span> 135 - 180 Days
             </li>
         </ul>
        </div>
    </div> --}}


 </div><!--end row-->
