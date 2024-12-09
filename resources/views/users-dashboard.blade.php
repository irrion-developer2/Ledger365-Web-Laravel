@extends("layouts.main")
@section('title', __('Dashboard | PreciseCA'))
@section("style")
    <link href="assets/plugins/vectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet"/>
@endsection
<style>
    #pieChart {
        width: 220px !important;
        height: 220px !important;
    }
</style>
@section("wrapper")
    <div class="page-wrapper">
            <div class="page-content">
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4">
                   <div class="col">
                     <div class="card radius-10 border-start border-0 border-4 border-info">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Cash</p>
                                    <h4 class="my-1 text-info">&#8377 {{ indian_format(abs($cashAmount)) }}</h4>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-blues text-white ms-auto"><i class='bx bxs-wallet'></i>
                                </div>
                            </div>
                        </div>
                     </div>
                   </div>
                    <div class="col">
                        <div class="card radius-10 border-start border-0 border-4 border-danger">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Bank</p>
                                    <h4 class="my-1 text-danger">&#8377 {{ indian_format(abs($cashBankAmount)) }}</h4>
                                </div>

                                    <div class="widgets-icons-2 rounded-circle bg-gradient-burning text-white ms-auto">
                                        <a class="nav-link " href="{{ route('reports.CashBank') }}"> <i class='bx bxs-bank'></i></a>
                                    </div>

                            </div>
                        </div>
                        </div>
                    </div>
                  {{--  <div class="col">
                    <div class="card radius-10 border-start border-0 border-4 border-success">
                       <div class="card-body">
                           <div class="d-flex align-items-center">
                               <div>
                                   <p class="mb-0 text-secondary">Inventory Amount</p>
                                   <h4 class="my-1 text-success">&#8377 {{ ($stockItemVoucherBalance) }}</h4>
                               </div>
                               <div class="widgets-icons-2 rounded-circle bg-gradient-ohhappiness text-white ms-auto">
                                <a class="nav-link " href="{{ route('stock-items.index') }}"> <i class='bx bxs-bar-chart-alt-2' ></i></a>
                               </div>
                           </div>
                       </div>
                    </div>
                  </div>  --}}
                  <div class="col">
                    <div class="card radius-10 border-start border-0 border-4 border-warning">
                       <div class="card-body">
                           <div class="d-flex align-items-center">
                               <div>
                                   <p class="mb-0 text-secondary">Payables</p>
                                   <h4 class="my-1 text-warning">&#8377 {{ indian_format(abs($payables)) }}
                                   </h4>
                               </div>
                               <div class="widgets-icons-2 rounded-circle bg-gradient-orange text-white ms-auto">
                                <a class="nav-link " href="{{ route('reports.daybook', ['voucher_type' => 'Credit Note']) }}"> <i class='lni lni-atlassian'></i></a>
                               </div>
                           </div>
                       </div>
                    </div>
                  </div>
                  
                    <div class="col">
                        <div class="card radius-10 border-start border-0 border-4 border-success">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">No. Of Customers</p>
                                    <h4 class="my-1 text-success">{{ ($numberOfCustomers) }}</h4>
                                </div>

                                    <div class="widgets-icons-2 rounded-circle bg-gradient-ohhappiness text-white ms-auto">
                                        <a class="nav-link " href="{{ route('customers.index') }}"> <i class='bx bxs-group'></i></a>
                                    </div>

                            </div>
                        </div>
                        </div>
                    </div>
                </div><!--end row-->

                @include('partials.dashboardSaleReceipt')

                {{-- <div class="card radius-10">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <div>
                                <h6 class="mb-0">Recent Orders</h6>
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
                         <div class="table-responsive">
                           <table class="table align-middle mb-0">
                            <thead class="table-light">
                             <tr>
                               <th>Product</th>
                               <th>Photo</th>
                               <th>Product ID</th>
                               <th>Status</th>
                               <th>Amount</th>
                               <th>Date</th>
                               <th>Shipping</th>
                             </tr>
                             </thead>
                             <tbody><tr>
                              <td>Iphone 5</td>
                              <td><img src="assets/images/products/01.png" class="product-img-2" alt="product img"></td>
                              <td>#9405822</td>
                              <td><span class="badge bg-gradient-quepal text-white shadow-sm w-100">Paid</span></td>
                              <td>$1250.00</td>
                              <td>03 Feb 2020</td>
                              <td><div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-gradient-quepal" role="progressbar" style="width: 100%"></div>
                                  </div></td>
                             </tr>

                             <tr>
                              <td>Earphone GL</td>
                              <td><img src="assets/images/products/02.png" class="product-img-2" alt="product img"></td>
                              <td>#8304620</td>
                              <td><span class="badge bg-gradient-blooker text-white shadow-sm w-100">Pending</span></td>
                              <td>$1500.00</td>
                              <td>05 Feb 2020</td>
                              <td><div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-gradient-blooker" role="progressbar" style="width: 60%"></div>
                                  </div></td>
                             </tr>

                             <tr>
                              <td>HD Hand Camera</td>
                              <td><img src="assets/images/products/03.png" class="product-img-2" alt="product img"></td>
                              <td>#4736890</td>
                              <td><span class="badge bg-gradient-bloody text-white shadow-sm w-100">Failed</span></td>
                              <td>$1400.00</td>
                              <td>06 Feb 2020</td>
                              <td><div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-gradient-bloody" role="progressbar" style="width: 70%"></div>
                                  </div></td>
                             </tr>

                             <tr>
                              <td>Clasic Shoes</td>
                              <td><img src="assets/images/products/04.png" class="product-img-2" alt="product img"></td>
                              <td>#8543765</td>
                              <td><span class="badge bg-gradient-quepal text-white shadow-sm w-100">Paid</span></td>
                              <td>$1200.00</td>
                              <td>14 Feb 2020</td>
                              <td><div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-gradient-quepal" role="progressbar" style="width: 100%"></div>
                                  </div></td>
                             </tr>
                             <tr>
                              <td>Sitting Chair</td>
                              <td><img src="assets/images/products/06.png" class="product-img-2" alt="product img"></td>
                              <td>#9629240</td>
                              <td><span class="badge bg-gradient-blooker text-white shadow-sm w-100">Pending</span></td>
                              <td>$1500.00</td>
                              <td>18 Feb 2020</td>
                              <td><div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-gradient-blooker" role="progressbar" style="width: 60%"></div>
                                  </div></td>
                             </tr>
                             <tr>
                              <td>Hand Watch</td>
                              <td><img src="assets/images/products/05.png" class="product-img-2" alt="product img"></td>
                              <td>#8506790</td>
                              <td><span class="badge bg-gradient-bloody text-white shadow-sm w-100">Failed</span></td>
                              <td>$1800.00</td>
                              <td>21 Feb 2020</td>
                              <td><div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-gradient-bloody" role="progressbar" style="width: 40%"></div>
                                  </div></td>
                             </tr>
                            </tbody>
                          </table>
                          </div>
                         </div>
                </div>


                    <div class="row">
                        <div class="col-12 col-lg-7 col-xl-8 d-flex">
                          <div class="card radius-10 w-100">
                            <div class="card-header bg-transparent">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <h6 class="mb-0">Recent Orders</h6>
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
                                <div class="row">
                                  <div class="col-lg-7 col-xl-8 border-end">
                                     <div id="geographic-map-2"></div>
                                  </div>
                                  <div class="col-lg-5 col-xl-4">

                                    <div class="mb-4">
                                    <p class="mb-2"><i class="flag-icon flag-icon-us me-1"></i> USA <span class="float-end">70%</span></p>
                                    <div class="progress" style="height: 7px;">
                                         <div class="progress-bar bg-primary progress-bar-striped" role="progressbar" style="width: 70%"></div>
                                     </div>
                                    </div>

                                    <div class="mb-4">
                                     <p class="mb-2"><i class="flag-icon flag-icon-ca me-1"></i> Canada <span class="float-end">65%</span></p>
                                     <div class="progress" style="height: 7px;">
                                         <div class="progress-bar bg-danger progress-bar-striped" role="progressbar" style="width: 65%"></div>
                                     </div>
                                    </div>

                                    <div class="mb-4">
                                     <p class="mb-2"><i class="flag-icon flag-icon-gb me-1"></i> England <span class="float-end">60%</span></p>
                                     <div class="progress" style="height: 7px;">
                                         <div class="progress-bar bg-success progress-bar-striped" role="progressbar" style="width: 60%"></div>
                                       </div>
                                    </div>

                                    <div class="mb-4">
                                     <p class="mb-2"><i class="flag-icon flag-icon-au me-1"></i> Australia <span class="float-end">55%</span></p>
                                     <div class="progress" style="height: 7px;">
                                         <div class="progress-bar bg-warning progress-bar-striped" role="progressbar" style="width: 55%"></div>
                                       </div>
                                    </div>

                                    <div class="mb-4">
                                     <p class="mb-2"><i class="flag-icon flag-icon-in me-1"></i> India <span class="float-end">50%</span></p>
                                     <div class="progress" style="height: 7px;">
                                         <div class="progress-bar bg-info progress-bar-striped" role="progressbar" style="width: 50%"></div>
                                       </div>
                                    </div>

                                    <div class="mb-0">
                                       <p class="mb-2"><i class="flag-icon flag-icon-cn me-1"></i> China <span class="float-end">45%</span></p>
                                       <div class="progress" style="height: 7px;">
                                           <div class="progress-bar bg-dark progress-bar-striped" role="progressbar" style="width: 45%"></div>
                                         </div>
                                    </div>

                                  </div>
                                </div>
                             </div>
                           </div>
                        </div>

                        <div class="col-12 col-lg-5 col-xl-4 d-flex">
                            <div class="card w-100 radius-10">
                             <div class="card-body">
                              <div class="card radius-10 border shadow-none">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <p class="mb-0 text-secondary">Total Likes</p>
                                            <h4 class="my-1">45.6M</h4>
                                            <p class="mb-0 font-13">+6.2% from last week</p>
                                        </div>
                                        <div class="widgets-icons-2 bg-gradient-cosmic text-white ms-auto"><i class='bx bxs-heart-circle'></i>
                                        </div>
                                    </div>
                                </div>
                             </div>
                             <div class="card radius-10 border shadow-none">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <p class="mb-0 text-secondary">Comments</p>
                                            <h4 class="my-1">25.6K</h4>
                                            <p class="mb-0 font-13">+3.7% from last week</p>
                                        </div>
                                        <div class="widgets-icons-2 bg-gradient-ibiza text-white ms-auto"><i class='bx bxs-comment-detail'></i>
                                        </div>
                                    </div>
                                </div>
                             </div>
                             <div class="card radius-10 mb-0 border shadow-none">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <p class="mb-0 text-secondary">Total Shares</p>
                                            <h4 class="my-1">85.4M</h4>
                                            <p class="mb-0 font-13">+4.6% from last week</p>
                                        </div>
                                        <div class="widgets-icons-2 bg-gradient-kyoto text-dark ms-auto"><i class='bx bxs-share-alt'></i>
                                        </div>
                                    </div>
                                </div>
                              </div>
                             </div>

                            </div>

                        </div>
                    </div><!--end row-->

                    <div class="row row-cols-1 row-cols-lg-3">
                         <div class="col d-flex">
                           <div class="card radius-10 w-100">
                               <div class="card-body">
                                <p class="font-weight-bold mb-1 text-secondary">Weekly Revenue</p>
                                <div class="d-flex align-items-center mb-4">
                                    <div>
                                        <h4 class="mb-0">$89,540</h4>
                                    </div>
                                    <div class="">
                                        <p class="mb-0 align-self-center font-weight-bold text-success ms-2">4.4% <i class="bx bxs-up-arrow-alt mr-2"></i>
                                        </p>
                                    </div>
                                </div>
                                <div class="chart-container-0 mt-5">
                                    <canvas id="chart3"></canvas>
                                  </div>
                               </div>
                           </div>
                         </div>
                         <div class="col d-flex">
                            <div class="card radius-10 w-100">
                                <div class="card-header bg-transparent">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <h6 class="mb-0">Orders Summary</h6>
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
                                    <div class="chart-container-1 mt-3">
                                        <canvas id="chart4"></canvas>
                                      </div>
                                </div>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex bg-transparent justify-content-between align-items-center border-top">Completed <span class="badge bg-gradient-quepal rounded-pill">25</span>
                                    </li>
                                    <li class="list-group-item d-flex bg-transparent justify-content-between align-items-center">Pending <span class="badge bg-gradient-ibiza rounded-pill">10</span>
                                    </li>
                                    <li class="list-group-item d-flex bg-transparent justify-content-between align-items-center">Process <span class="badge bg-gradient-deepblue rounded-pill">65</span>
                                    </li>
                                </ul>
                            </div>
                          </div>
                          <div class="col d-flex">
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
                          </div>
                    </div><!--end row--> --}}

            </div>
    </div>
@endsection

@section("script")
    <script src="assets/plugins/vectormap/jquery-jvectormap-2.0.2.min.js"></script>
    <script src="assets/plugins/vectormap/jquery-jvectormap-world-mill-en.js"></script>
    <script src="assets/plugins/chartjs/js/chart.js"></script>
    <script src="assets/js/index.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sample data (Replace this with @json($pieChartDataOverall) in a real scenario)
            var pieChartData = @json($pieChartDataOverall);

            // Prepare data for Chart.js
            var labels = Object.keys(pieChartData);
            var data = Object.values(pieChartData).map(function(value) {
                return Math.ceil(Math.abs(value)); // Remove negative signs and round up values
            });

            // Combine labels and data into an array of objects
            var combinedData = labels.map(function(label, index) {
                return { label: label, value: data[index] };
            });

            // Sort the array by value in descending order
            combinedData.sort(function(a, b) {
                return b.value - a.value;
            });

            // Limit to the top 5 highest amounts
            var topData = combinedData.slice(0, 5);

            // Extract labels and data after sorting
            labels = topData.map(function(item) {
                return item.label;
            });
            data = topData.map(function(item) {
                return item.value;
            });

            // Define a set of colors
            var colors = ['#14abef', '#ffc107', '#b02a37', '#4bc0c0', '#ff9f40', '#36a2eb', '#ff6384', '#cc65fe', '#ffce56', '#fd6b19'];

            // Generate background colors dynamically
            var backgroundColors = colors.slice(0, labels.length);

            // Create the doughnut chart
            var ctx = document.getElementById('pieChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',  // Changed to doughnut chart
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: backgroundColors,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false // Hide legend if badges are used instead
                        }
                    }
                }
            });

            // Generate badges dynamically for the top 5 highest amounts
            var badgeList = document.getElementById('badge-list');
            badgeList.innerHTML = '';  // Clear existing badges

            labels.forEach((label, index) => {
                var listItem = document.createElement('li');
                listItem.className = 'list-group-item d-flex bg-transparent justify-content-between align-items-center';

                var badge = document.createElement('span');
                badge.className = 'badge rounded-pill';
                badge.style.backgroundColor = backgroundColors[index];
                badge.style.color = '#fff'; // Ensure text is readable on colored backgrounds
                badge.textContent = `₹ ${data[index]}`; // Display the amount with Rupees sign

                listItem.appendChild(document.createTextNode(label)); // Add label text
                listItem.appendChild(badge); // Add badge

                badgeList.appendChild(listItem);
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var ctx = document.getElementById('salereceiptchart').getContext('2d');
            var chart; // Declare chart globally

            function preprocessData(data) {
                return Object.values(data).map(value => {
                    return parseFloat(Math.abs(value)).toFixed(2);
                });
            }

            function createChart(chartData) {
                var salesData = preprocessData(chartData.sales);
                var receiptsData = preprocessData(chartData.receipts);

                if (chart) {
                    chart.destroy(); // Destroy the previous chart before creating a new one
                }

                chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(chartData.sales),
                        datasets: [
                            {
                                label: 'Sales',
                                data: salesData,
                                backgroundColor: '#14abef',
                                borderColor: '#14abef',
                                borderWidth: 2,
                                barPercentage: 0.5,
                                borderRadius: 10
                            },
                            {
                                label: 'Receipts',
                                data: receiptsData,
                                backgroundColor: '#ffc107',
                                borderColor: '#ffc107',
                                borderWidth: 2,
                                barPercentage: 0.5,
                                borderRadius: 10
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    color: '#333'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (tooltipItem) {
                                        return tooltipItem.dataset.label + ': ' + parseFloat(Math.abs(tooltipItem.raw)).toFixed(2);
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Months'
                                },
                                ticks: {
                                    color: '#555'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Amount'
                                },
                                ticks: {
                                    color: '#555',
                                    callback: function (value) {
                                        return parseFloat(Math.abs(value)).toFixed(2);
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Function to fetch chart data and update the chart and totals
            function fetchChartData(filter) {
                $.ajax({
                    url: '/get-filtered-data', // Your Laravel route for fetching filtered data
                    type: 'GET',
                    data: { filter: filter },
                    success: function (response) {
                        createChart(response.chartData); // Update chart with the new data
                        // Update sales and receipt totals
                        $('.sales-total').text('₹ ' + parseFloat(response.salesTotal).toFixed(2));
                        $('.receipts-total').text('₹ ' + parseFloat(response.receiptsTotal).toFixed(2));
                    },
                    error: function (error) {
                        console.error('Error fetching filtered data:', error);
                    }
                });
            }

            // Function to extract filter from the URL
            function getFilterFromURL() {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get('filter') || 'this_year'; // Default to 'this_year' if no filter is found
            }

            // Function to update URL with selected filter
            function setFilterInURL(filter) {
                const url = new URL(window.location);
                url.searchParams.set('filter', filter);
                window.history.pushState({}, '', url); // Update the URL without reloading the page
            }

            // Handle the event when a filter option is selected from the dropdown
            document.querySelectorAll('.filter-option').forEach(function (filterOption) {
                filterOption.addEventListener('click', function () {
                    var filter = this.getAttribute('data-filter');
                    setFilterInURL(filter); // Update the URL with the selected filter
                    fetchChartData(filter); // Fetch and update the chart data
                });
            });

            // On page load, fetch chart data based on the filter in the URL
            function initializeChart() {
                const filter = getFilterFromURL();
                fetchChartData(filter); // Fetch and display chart based on the filter in URL
            }

            // Event listener to handle browser's back/forward buttons (popstate)
            window.addEventListener('popstate', function () {
                initializeChart(); // Reload chart based on the URL when navigating back/forward
            });

            // Initial chart load
            initializeChart(); // Fetch and display the chart when the page is loaded
        });
    </script>

@endsection

