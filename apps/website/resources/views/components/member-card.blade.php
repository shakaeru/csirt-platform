{{-- Kartu satu anggota pengurus. Tanpa foto → avatar inisial (pola "avatar placeholder" Flowbite).
     `large` = Pembina/Pengurus Inti; di ponsel ukurannya sama dengan kartu divisi supaya muat dua per baris. --}}
@props(['member', 'large' => false])

<div {{ $attributes->class(['flex flex-col items-center text-center']) }}>
    @if ($member->photo_url)
        <img src="{{ $member->photo_url }}" alt="Foto {{ $member->name }}" width="600" height="600" loading="lazy"
             @class(['h-24 w-24 rounded-full object-cover ring-4 ring-csirt-neutral-100', 'sm:h-32 sm:w-32' => $large])>
    @else
        <div aria-hidden="true"
             @class(['inline-flex h-24 w-24 items-center justify-center rounded-full bg-csirt-primary text-2xl font-semibold text-csirt-white ring-4 ring-csirt-neutral-100',
                     'sm:h-32 sm:w-32 sm:text-3xl' => $large])>
            {{ $member->initials }}
        </div>
    @endif
    <p @class(['mt-4 font-semibold text-csirt-navy', 'sm:text-lg' => $large])>{{ $member->name }}</p>
    <p class="text-sm text-csirt-neutral-600">{{ $member->position }}</p>
</div>
