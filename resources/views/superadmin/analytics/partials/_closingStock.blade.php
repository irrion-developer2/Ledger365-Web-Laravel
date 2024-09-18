
<div class="row row-cols-1 row-cols-lg-2">
    {{-- <div class="col d-flex">
      <div class="card radius-10 w-100">
          <div class="card-body">
           <p class="font-weight-bold mb-1 text-secondary">Closing Stock</p>
           <div class="d-flex align-items-center mb-4">
               <div>
                   <h4 class="mb-0">{{ $ClosingStock }}</h4>
               </div>
           </div>
           <div class="chart-container-0 mt-5">
               <canvas id="chart3"></canvas>
             </div>
          </div>
      </div>
    </div> --}}

    <div class="col d-flex">
        <div class="card radius-10 w-100">
            <div class="card-body">
             <p class="font-weight-bold mb-1 text-secondary">Closing Stock</p>
             <div class="d-flex align-items-center mb-4">
                 <div>
                     <h4 class="mb-0">{{ $ClosingStock }}</h4>
                 </div>
                 {{-- <div class="">
                     <p class="mb-0 align-self-center font-weight-bold text-success ms-2">4.4% <i class="bx bxs-up-arrow-alt mr-2"></i>
                     </p>
                 </div> --}}
             </div>
             <div class="chart-container-0 mt-5">
                 <canvas id="closingStockChart"></canvas>
               </div>
            </div>
        </div>
      </div>

      {{-- @dd($closingStockData); --}}

    {{-- <div class="col d-flex">
        <div class="card radius-10 w-100">
            <div class="card-body">
                <p class="font-weight-bold mb-1 text-secondary">Closing Stock</p>
                <div class="d-flex align-items-center mb-4">
                    <div>
                        
                    </div>
                </div>
                <div class="chart-container-0 mt-5">
                    <canvas id="closingStockChart"></canvas>
                </div>
            </div>
        </div>
    </div> --}}
    {{-- @dd($closingStockData); --}}

     {{-- <div class="col d-flex">
       <div class="card radius-10 w-100">
            <div class="card-header bg-transparent">
               <div class="d-flex align-items-center">
                   <div>
                       <h6 class="mb-0">Top Selling Categories</h6>
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
              <div class="chart-container-0">
                <canvas id="chart5"></canvas>
              </div>
           </div>
           <div class="row row-group border-top g-0">
               <div class="col">
                   <div class="p-3 text-center">
                       <h4 class="mb-0 text-danger">$45,216</h4>
                       <p class="mb-0">Clothing</p>
                   </div>
               </div>
               <div class="col">
                   <div class="p-3 text-center">
                       <h4 class="mb-0 text-success">$68,154</h4>
                       <p class="mb-0">Electronic</p>
                   </div>
                </div>
           </div><!--end row-->
       </div>
     </div> --}}
</div><!--end row-->