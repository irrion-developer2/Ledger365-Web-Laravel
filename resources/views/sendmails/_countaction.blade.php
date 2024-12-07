

<button class="btn btn-primary btn-sm ms-auto count-mutliple-mail" 
        data-company-id="{{$company_id}}" 
        data-date="{{$date}}">
    Send Mail To All
</button>

<button class="btn btn-primary btn-sm ms-auto count-mutliple-mail" 
        data-company-id="{{$company_id}}"
        data-date="{{$date}}">
    View
</button>

<script>

    $(document).off('click', '.count-mutliple-mail').on('click', '.count-mutliple-mail', function () {
        var companyId = $(this).data('company-id');
        var date = $(this).data('date');

        console.log('Company ID:', companyId, 'Date:', date);

        $.ajax({
            url: "{{ route('count-mutliple-mail') }}",
            method: "GET",
            data: {
                company_id: companyId,
                date: date,
                _token: "{{ csrf_token() }}"
            }
        });
    });

</script>