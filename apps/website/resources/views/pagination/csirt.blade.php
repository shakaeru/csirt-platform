{{-- Paginasi sederhana dengan warna token csirt-* (view bawaan Laravel memakai warna default Tailwind). --}}
@if ($paginator->hasPages())
    @php $button = 'rounded-base border px-4 py-2 text-sm font-medium'; @endphp
    <nav role="navigation" aria-label="Navigasi halaman" class="mt-10 flex items-center justify-between gap-4">
        @if ($paginator->onFirstPage())
            <span class="{{ $button }} cursor-default border-csirt-neutral-100 text-csirt-neutral-600" aria-disabled="true">← Sebelumnya</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $button }} border-csirt-neutral-100 text-csirt-navy hover:border-csirt-primary">← Sebelumnya</a>
        @endif

        <span class="text-sm text-csirt-neutral-600">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $button }} border-csirt-neutral-100 text-csirt-navy hover:border-csirt-primary">Berikutnya →</a>
        @else
            <span class="{{ $button }} cursor-default border-csirt-neutral-100 text-csirt-neutral-600" aria-disabled="true">Berikutnya →</span>
        @endif
    </nav>
@endif
