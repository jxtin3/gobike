{{--
    One <template> per loading placeholder. resources/js/admin/skeleton.js clones the right one over the page (or over
    the list) when the next page takes longer than a moment, e.g. <a data-skeleton="table">.
    Templates never render, so they cost nothing until used.
--}}
@foreach (['head', 'rows', 'table', 'grid', 'detail', 'form', 'dashboard', 'map', 'document'] as $skeleton)
    <template data-skeleton-template="{{ $skeleton }}"><x-dynamic-component :component="'admin.skeleton.'.$skeleton" /></template>
@endforeach
