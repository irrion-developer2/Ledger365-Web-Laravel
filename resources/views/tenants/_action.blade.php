<div class="d-lg-flex order-actions align-items-center">
    <a class="" href="{{ route('tenants.edit', $tenant->id) }}" data-bs-toggle="tooltip"
        data-bs-placement="bottom" data-bs-original-title="{{ __('Edit') }}">
        <i class="bx bxs-edit"></i>
    </a>

    <form method="POST" action="{{ route('tenants.destroy', $tenant->id) }}" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="border-0 ms-3" style="font-size: 20px; border-radius: 20%;" data-bs-toggle="tooltip" data-bs-placement="bottom"
            data-bs-original-title="{{ __('Delete') }}" onclick="return confirm('Are you sure you want to delete this tenant?');">
            <i class="bx bxs-trash"></i>
        </button>
    </form>
</div>