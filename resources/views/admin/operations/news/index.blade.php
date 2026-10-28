@extends('admin.operations.layout')
@section('title', 'News')
@section('heading', 'News')
@section('subheading', 'Posts shown on the News page of the website.')
@section('actions')
    <a class="btn btn-primary" href="{{ route('admin.operations.news.create') }}"><x-admin.icon name="plus" /> Add news</a>
@endsection

@section('content')
@php($searching = filled(request('search')))
<x-admin.filters :action="route('admin.operations.news.index')" placeholder="Search by title" label="Search news" :filtered="$searching" />

<div data-list>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Publication date</th>
                <th>Updated</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($news as $item)
                <tr data-href="{{ route('admin.operations.news.show', $item) }}">
                    <td>
                        <b><a class="cell-link" href="{{ route('admin.operations.news.show', $item) }}" title="{{ $item->title }}" data-skeleton="detail">{{ Str::limit($item->title, 70) }}</a></b>
                        <small class="sub-line">{{ Str::limit($item->summary, 90) }}</small>
                    </td>
                    <td><span class="pill pill-{{ $item->status_pill }}">{{ $item->status_label }}</span></td>
                    <td>{{ $item->published_at?->format('M j, Y') ?? '—' }}</td>
                    <td>{{ $item->updated_at->format('M j, Y') }}</td>
                    <td>
                        <div class="row-actions">
                            <a class="icon-btn" href="{{ route('admin.operations.news.show', $item) }}" title="View" aria-label="View {{ $item->title }}" data-skeleton="detail"><x-admin.icon name="eye" /></a>
                            <a class="icon-btn" href="{{ route('admin.operations.news.edit', $item) }}" title="Edit" aria-label="Edit {{ $item->title }}" data-skeleton="form"><x-admin.icon name="edit" /></a>
                            <form method="POST" action="{{ route('admin.operations.news.destroy', $item) }}"
                                  data-confirm-title="Delete this post?"
                                  data-confirm-text="“{{ Str::limit($item->title, 80) }}” and its image will be permanently deleted."
                                  data-confirm-ok="Delete post">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="icon-btn danger" title="Delete" aria-label="Delete {{ $item->title }}"><x-admin.icon name="trash" /></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                @if ($searching)
                    <x-admin.empty :row="5" icon="file-text" title="No posts match your search" :clear="route('admin.operations.news.index')">
                        Try a different title.
                    </x-admin.empty>
                @else
                    <x-admin.empty :row="5" icon="file-text" title="No news yet">
                        Write the first post for the News page.
                        <x-slot:action><a class="btn btn-secondary btn-sm" href="{{ route('admin.operations.news.create') }}"><x-admin.icon name="plus" /> Add news</a></x-slot:action>
                    </x-admin.empty>
                @endif
            @endforelse
        </tbody>
    </table>
</div>

    <x-admin.pager :paginator="$news" noun="post" />
</div>
@endsection
