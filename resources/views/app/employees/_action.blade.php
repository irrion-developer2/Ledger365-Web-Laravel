<button class="btn {{ $data->status ? 'btn-success' : 'btn-danger' }} dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
    {{ $data->status ? 'Active' : 'Inactive' }}
</button>
<ul class="dropdown-menu">
    <li><a class="dropdown-item" href="#" onclick="changeStatus({{ $data->id }}, 1)">Active</a></li>
    <li><a class="dropdown-item" href="#" onclick="changeStatus({{ $data->id }}, 0)">Inactive</a></li>
</ul>
