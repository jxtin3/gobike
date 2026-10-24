{{-- Pagination footer: always the count, page links when there is more than one page. `noun` is singular ("user"). --}}
@props(['paginator', 'noun' => 'result'])
{{ $paginator->onEachSide(1)->links('admin.partials.pagination', ['noun' => $noun]) }}
