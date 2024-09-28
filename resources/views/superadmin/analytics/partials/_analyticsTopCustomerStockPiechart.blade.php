<div class="col-lg-12">
    <div class="row">
        <div class="col-12 col-lg-4 col-xl-4 d-flex">
        <div class="card radius-10 w-100">
            <div class="card-header bg-transparent">
                <div class="d-flex align-items-center">
                    <div>
                        <h6 class="mb-0">Top N Customers</h6>
                    </div>
                    {{-- <div class="dropdown ms-auto">
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
                    </div> --}}
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12 col-xl-12">
                        @foreach ($topCustomers as $customer)
                            <div class="mb-4">
                                <p class="mb-2">{{ $customer['name'] }}
                                    <span class="float-end">{{ number_format($customer['sales'], 2) }}</span>
                                </p>
                                <div class="progress" style="height: 7px;">
                                    <div class="progress-bar
                                        @if($loop->first) bg-primary
                                        @elseif($loop->index == 1) bg-danger
                                        @elseif($loop->index == 2) bg-success
                                        @elseif($loop->index == 3) bg-warning
                                        @else bg-info
                                        @endif
                                        progress-bar-striped"
                                        role="progressbar"
                                        style="width: {{ $maxSales > 0 ? min(100, $customer['sales'] / $maxSales * 100) : 0 }}%">
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>
        </div>
        </div>

        <div class="col-12 col-lg-4 col-xl-4 d-flex">
            <div class="card radius-10 w-100">
            <div class="card-header bg-transparent">
                <div class="d-flex align-items-center">
                    <div>
                        <h6 class="mb-0">Top N Stock</h6>
                    </div>
                    {{-- <div class="dropdown ms-auto">
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
                    </div> --}}
                </div>
                </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12 col-xl-12">
                        {{-- @foreach ($top5StockItems as $stockItem)
                            <div class="mb-4">
                                <p class="mb-2">{{ $stockItem['name'] }}
                                    <span class="float-end">{{ number_format($stockItem['stock_value'], 2) }}</span>
                                </p>
                                <div class="progress" style="height: 7px;">
                                    <div class="progress-bar
                                        @if($loop->first) bg-primary
                                        @elseif($loop->index == 1) bg-danger
                                        @elseif($loop->index == 2) bg-success
                                        @elseif($loop->index == 3) bg-warning
                                        @else bg-info
                                        @endif
                                        progress-bar-striped"
                                        role="progressbar"
                                        style="width: {{ $maxStockValue > 0 ? min(100, $stockItem['stock_value'] / $maxStockValue * 100) : 0 }}%">
                                    </div>
                                </div>
                            </div>
                        @endforeach --}}
                    </div>
                </div>
            </div>
            </div>
        </div>

        <div class="col-12 col-lg-4 col-xl-4 d-flex">
            <div class="card radius-10 w-100">
            <div class="card-header bg-transparent">
                <div class="d-flex align-items-center">
                    <div>
                        <h6 class="mb-0">Customer Category</h6>
                    </div>
                    {{-- <div class="dropdown ms-auto">
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
                    </div> --}}

                    {{-- <div class="highcharts-a11y-proxy-container" aria-hidden="false"><div aria-label="Chart menu" role="region" aria-hidden="false"><button aria-label="View chart menu" aria-expanded="false" class="highcharts-a11y-proxy-button" aria-hidden="false" style="border-width: 0px; background-color: transparent; cursor: pointer; outline: none; opacity: 0.001; z-index: 999; overflow: hidden; padding: 0px; margin: 0px; display: block; position: absolute; width: 24px; height: 22px; top: 10.5px;"></button></div></div> --}}

                </div>
                </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12 col-xl-12">

                        <div class="chart-container-1 mt-3">
                            <canvas id="chartCustomerCategory"></canvas>
                        </div>

                    </div>
                </div>
            </div>
            </div>
        </div>

    </div>
</div><!--end row-->
