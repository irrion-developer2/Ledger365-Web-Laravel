<!-- resources/views/app/companies/_action.blade.php -->

<button type="button" 
        name="delete" 
        class="delete btn btn-danger btn-sm" 
        data-route="{{ route('usercompanies.delete') }}" 
        data-id="{{ $data->company_id }}">
    Delete
</button>
