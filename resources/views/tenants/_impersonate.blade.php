@foreach($tenant->domains as $domain)
        <a href="{{ !empty($domain->domain) ? 'http://' . $domain->domain . ':8000' : '#' }}" target="_blank" class="btn btn-outline-info px-5 radius-30">Impersonate</a>
@endforeach