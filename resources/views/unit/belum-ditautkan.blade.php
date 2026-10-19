@extends('unit.layout')

@section('title', 'Akun Belum Ditautkan')

@section('content')

    <div class="w-full px-margin py-space-lg flex flex-col gap-space-lg">

        <!-- Strip Judul: alasan konsol unit belum bisa dibuka. -->
        <div class="flex flex-col">
            <div class="flex items-center gap-space-xs text-secondary font-label-md text-label-md uppercase tracking-wider">
                <span class="w-2 h-2 rounded-full bg-error animate-ping" aria-hidden="true"></span>
                Konsol Unit &bull; Akses Belum Lengkap
            </div>

            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight mt-0.5">
                Akun Belum Ditautkan
            </h1>

            <p class="font-body-md text-body-md text-on-surface-variant max-w-4xl">
                Akun ini belum tercatat sebagai pengurus unit layanan mana pun,
                sehingga dashboard unit belum bisa dibuka. Sengaja tidak ada daftar
                unit yang ditampilkan di sini: akses hanya terbuka setelah admin
                humas menautkan akun ini ke unit yang memang Anda tangani.
            </p>
        </div>
        <!-- End of Strip Judul -->

        <!-- Kartu Arahan: langkah petugas sebelum akunnya bisa dipakai. -->
        <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-sm flex flex-col gap-space-sm max-w-3xl">
            <span class="flex items-start gap-space-sm font-body-sm text-body-sm text-on-surface-variant">
                <x-symbol nama="alternate_email" class="text-[18px] text-secondary shrink-0" />
                <span>
                    Sebutkan alamat email akun ini kepada admin humas beserta nama unit
                    yang Anda tangani, supaya penautan tidak salah sasaran.
                </span>
            </span>

            <span class="flex items-start gap-space-sm font-body-sm text-body-sm text-on-surface-variant">
                <x-symbol nama="refresh" class="text-[18px] text-outline shrink-0" />
                <span>
                    Setelah akun ditautkan, muat ulang halaman ini; Anda akan langsung
                    dibawa ke dashboard unit tersebut tanpa perlu memilih unit.
                </span>
            </span>

            <span class="flex items-start gap-space-sm font-body-sm text-body-sm text-on-surface-variant">
                <x-symbol nama="logout" class="text-[18px] text-outline shrink-0" />
                <span>Untuk berpindah akun, gunakan tombol Keluar di kanan atas.</span>
            </span>
        </div>
        <!-- End of Kartu Arahan -->

    </div>
@endsection
