<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pratinjau Sertifikat — XCODE Training</title>
    <style>
        :root {
            --garis: #d9d9d4;
            --teks: #1b1b18;
            --redup: #6f6f68;
            --aksen: #b3261e;
            --latar: #f4f4f1;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--teks);
            background: var(--latar);
        }

        header {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 0.5rem 1rem;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--garis);
            background: #fff;
        }

        header h1 { margin: 0; font-size: 1.05rem; letter-spacing: 0.01em; }
        header p { margin: 0; font-size: 0.8rem; color: var(--redup); }

        form {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 1rem;
            padding: 1rem 1.5rem;
            background: #fff;
            border-bottom: 1px solid var(--garis);
        }

        label { display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.75rem; color: var(--redup); }

        select, button {
            font: inherit;
            font-size: 0.85rem;
            padding: 0.4rem 0.55rem;
            border: 1px solid var(--garis);
            border-radius: 0.35rem;
            background: #fff;
            color: var(--teks);
        }

        .cek { flex-direction: row; align-items: center; gap: 0.45rem; font-size: 0.85rem; color: var(--teks); }
        .cek input { width: 1rem; height: 1rem; }

        button { cursor: pointer; background: var(--teks); border-color: var(--teks); color: #fff; padding: 0.45rem 1rem; }
        button:hover { opacity: 0.85; }

        main { padding: 1rem 1.5rem 1.5rem; }

        iframe {
            width: 100%;
            height: min(78vh, 900px);
            border: 1px solid var(--garis);
            border-radius: 0.5rem;
            background: #fff;
        }

        .keterangan { display: flex; flex-wrap: wrap; gap: 0.35rem 1rem; margin: 0 0 0.75rem; font-size: 0.8rem; color: var(--redup); }
        .keterangan a { color: var(--aksen); }
        .keterangan code { font-size: 0.78rem; }
    </style>
</head>
<body>
    <header>
        <h1>Pratinjau Sertifikat</h1>
        <p>Periksa hasil render PDF tanpa harus mengunduh berkas. Data yang dipakai adalah contoh kalibrasi.</p>
    </header>

    <form method="GET" action="{{ route('pratinjau') }}" id="pilihan">
        <label>
            Jenis layout
            <select name="jenis" onchange="document.getElementById('pilihan').submit()">
                @foreach ($jenisTersedia as $item)
                    <option value="{{ $item }}" @selected($item === $jenis)>{{ ucfirst($item) }}</option>
                @endforeach
            </select>
        </label>

        <label>
            Peserta contoh
            <select name="sampel" onchange="document.getElementById('pilihan').submit()">
                @foreach ($semuaSampel as $item)
                    <option value="{{ $item['berkas'] }}" @selected($item['berkas'] === $sampel['berkas'])>
                        {{ $item['data']['nama_peserta'] }}
                    </option>
                @endforeach
            </select>
        </label>

        <label>
            Mode
            <select name="mode" onchange="document.getElementById('pilihan').submit()">
                @foreach ($modeTersedia as $item)
                    <option value="{{ $item->value }}" @selected($item === $mode)>{{ $item->label() }}</option>
                @endforeach
            </select>
        </label>

        <label class="cek">
            <input type="checkbox" name="guides" value="1" @checked($guides)
                   onchange="document.getElementById('pilihan').submit()">
            Garis bantu
        </label>

        <button type="submit">Muat ulang</button>
    </form>

    <main>
        <p class="keterangan">
            <span>Berkas: <code>{{ $sampel['berkas'] }}</code></span>
            <span><a href="{{ $pdfUrl }}" target="_blank" rel="noopener">Buka PDF di tab baru</a></span>
            @if ($mode !== \App\Enums\CertificateMode::Terbit)
                <span>Mode {{ $mode->label() }} menambahkan tanda air.</span>
            @endif
            @if ($guides)
                <span>Garis bantu 10 mm dan koordinat elemen ikut tercetak.</span>
            @endif
        </p>

        <iframe src="{{ $pdfUrl }}" title="Pratinjau {{ $sampel['berkas'] }}"></iframe>
    </main>
</body>
</html>
