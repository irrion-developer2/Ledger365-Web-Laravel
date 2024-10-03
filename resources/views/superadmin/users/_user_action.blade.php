<button class="btn {{ $user->status ? 'btn-success' : 'btn-danger' }} dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
    {{ $user->status ? 'Active' : 'Inactive' }}
</button>
<ul class="dropdown-menu">
    <li><a class="dropdown-item" href="#" onclick="changeStatus({{ $user->id }}, 1)">Active</a></li>
    <li><a class="dropdown-item" href="#" onclick="changeStatus({{ $user->id }}, 0)">Inactive</a></li>
</ul>
