
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
                     {{-- <h4 class="mb-0">{{ $ClosingStock }}</h4> --}}
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
</div><!--end row-->
