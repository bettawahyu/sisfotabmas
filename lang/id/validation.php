<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa Validasi
    |--------------------------------------------------------------------------
    */

    'accepted' => 'Isian :attribute harus diterima.',
    'accepted_if' => 'Isian :attribute harus diterima jika :other bernilai :value.',
    'active_url' => 'Isian :attribute harus berupa URL yang valid.',
    'after' => 'Isian :attribute harus berupa tanggal setelah :date.',
    'after_or_equal' => 'Isian :attribute harus berupa tanggal setelah atau sama dengan :date.',
    'alpha' => 'Isian :attribute hanya boleh berisi huruf.',
    'alpha_dash' => 'Isian :attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => 'Isian :attribute hanya boleh berisi huruf dan angka.',
    'any_of' => 'Isian :attribute tidak valid.',
    'array' => 'Isian :attribute harus berupa larik.',
    'array_keys' => 'Isian :attribute hanya boleh berisi kunci berikut: :values.',
    'ascii' => 'Isian :attribute hanya boleh berisi karakter alfanumerik dan simbol satu bita.',
    'base64' => 'Isian :attribute harus berupa string Base64 yang valid.',
    'before' => 'Isian :attribute harus berupa tanggal sebelum :date.',
    'before_or_equal' => 'Isian :attribute harus berupa tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => 'Isian :attribute harus berisi antara :min dan :max item.',
        'file' => 'Isian :attribute harus berukuran antara :min dan :max kilobita.',
        'numeric' => 'Isian :attribute harus bernilai antara :min dan :max.',
        'string' => 'Isian :attribute harus berisi antara :min dan :max karakter.',
    ],
    'boolean' => 'Isian :attribute harus bernilai benar atau salah.',
    'can' => 'Isian :attribute berisi nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'contains' => 'Isian :attribute tidak memuat nilai yang diwajibkan.',
    'current_password' => 'Kata sandi salah.',
    'date' => 'Isian :attribute harus berupa tanggal yang valid.',
    'date_equals' => 'Isian :attribute harus berupa tanggal yang sama dengan :date.',
    'date_format' => 'Isian :attribute harus sesuai format :format.',
    'decimal' => 'Isian :attribute harus memiliki :decimal angka desimal.',
    'declined' => 'Isian :attribute harus ditolak.',
    'declined_if' => 'Isian :attribute harus ditolak jika :other bernilai :value.',
    'different' => 'Isian :attribute dan :other harus berbeda.',
    'digits' => 'Isian :attribute harus terdiri dari :digits digit.',
    'digits_between' => 'Isian :attribute harus terdiri dari :min sampai :max digit.',
    'dimensions' => 'Dimensi gambar :attribute tidak valid.',
    'distinct' => 'Isian :attribute memiliki nilai ganda.',
    'doesnt_contain' => 'Isian :attribute tidak boleh memuat salah satu dari: :values.',
    'doesnt_end_with' => 'Isian :attribute tidak boleh diakhiri salah satu dari: :values.',
    'doesnt_start_with' => 'Isian :attribute tidak boleh diawali salah satu dari: :values.',
    'email' => 'Isian :attribute harus berupa alamat email yang valid.',
    'encoding' => 'Isian :attribute harus dienkode dalam :encoding.',
    'ends_with' => 'Isian :attribute harus diakhiri salah satu dari: :values.',
    'enum' => ':attribute yang dipilih tidak valid.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'extensions' => 'Isian :attribute harus berekstensi salah satu dari: :values.',
    'file' => 'Isian :attribute harus berupa berkas.',
    'filled' => 'Isian :attribute harus memiliki nilai.',
    'gt' => [
        'array' => 'Isian :attribute harus berisi lebih dari :value item.',
        'file' => 'Isian :attribute harus berukuran lebih dari :value kilobita.',
        'numeric' => 'Isian :attribute harus lebih besar dari :value.',
        'string' => 'Isian :attribute harus berisi lebih dari :value karakter.',
    ],
    'gte' => [
        'array' => 'Isian :attribute harus berisi :value item atau lebih.',
        'file' => 'Isian :attribute harus berukuran lebih dari atau sama dengan :value kilobita.',
        'numeric' => 'Isian :attribute harus lebih besar dari atau sama dengan :value.',
        'string' => 'Isian :attribute harus berisi :value karakter atau lebih.',
    ],
    'hex_color' => 'Isian :attribute harus berupa warna heksadesimal yang valid.',
    'image' => 'Isian :attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'in_array' => 'Isian :attribute harus ada di :other.',
    'in_array_keys' => 'Isian :attribute harus memuat setidaknya satu kunci berikut: :values.',
    'integer' => 'Isian :attribute harus berupa bilangan bulat.',
    'ip' => 'Isian :attribute harus berupa alamat IP yang valid.',
    'ipv4' => 'Isian :attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => 'Isian :attribute harus berupa alamat IPv6 yang valid.',
    'json' => 'Isian :attribute harus berupa string JSON yang valid.',
    'list' => 'Isian :attribute harus berupa daftar.',
    'lowercase' => 'Isian :attribute harus berhuruf kecil.',
    'lt' => [
        'array' => 'Isian :attribute harus berisi kurang dari :value item.',
        'file' => 'Isian :attribute harus berukuran kurang dari :value kilobita.',
        'numeric' => 'Isian :attribute harus kurang dari :value.',
        'string' => 'Isian :attribute harus berisi kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => 'Isian :attribute tidak boleh berisi lebih dari :value item.',
        'file' => 'Isian :attribute harus berukuran kurang dari atau sama dengan :value kilobita.',
        'numeric' => 'Isian :attribute harus kurang dari atau sama dengan :value.',
        'string' => 'Isian :attribute harus berisi :value karakter atau kurang.',
    ],
    'mac_address' => 'Isian :attribute harus berupa alamat MAC yang valid.',
    'max' => [
        'array' => 'Isian :attribute tidak boleh berisi lebih dari :max item.',
        'file' => 'Isian :attribute tidak boleh lebih dari :max kilobita.',
        'numeric' => 'Isian :attribute tidak boleh lebih dari :max.',
        'string' => 'Isian :attribute tidak boleh lebih dari :max karakter.',
    ],
    'max_digits' => 'Isian :attribute tidak boleh lebih dari :max digit.',
    'mimes' => 'Isian :attribute harus berupa berkas berjenis: :values.',
    'mimetypes' => 'Isian :attribute harus berupa berkas berjenis: :values.',
    'min' => [
        'array' => 'Isian :attribute harus berisi minimal :min item.',
        'file' => 'Isian :attribute harus berukuran minimal :min kilobita.',
        'numeric' => 'Isian :attribute minimal bernilai :min.',
        'string' => 'Isian :attribute minimal berisi :min karakter.',
    ],
    'min_digits' => 'Isian :attribute minimal terdiri dari :min digit.',
    'missing' => 'Isian :attribute tidak boleh ada.',
    'missing_if' => 'Isian :attribute tidak boleh ada jika :other bernilai :value.',
    'missing_unless' => 'Isian :attribute tidak boleh ada kecuali :other bernilai :value.',
    'missing_with' => 'Isian :attribute tidak boleh ada jika :values ada.',
    'missing_with_all' => 'Isian :attribute tidak boleh ada jika :values ada.',
    'multiple_of' => 'Isian :attribute harus kelipatan :value.',
    'not_in' => ':attribute yang dipilih tidak valid.',
    'not_regex' => 'Format isian :attribute tidak valid.',
    'numeric' => 'Isian :attribute harus berupa angka.',
    'password' => [
        'letters' => 'Isian :attribute harus berisi setidaknya satu huruf.',
        'mixed' => 'Isian :attribute harus berisi setidaknya satu huruf kapital dan satu huruf kecil.',
        'numbers' => 'Isian :attribute harus berisi setidaknya satu angka.',
        'symbols' => 'Isian :attribute harus berisi setidaknya satu simbol.',
        'uncompromised' => ':attribute ini pernah muncul dalam kebocoran data. Silakan pilih :attribute lain.',
    ],
    'present' => 'Isian :attribute wajib ada.',
    'present_if' => 'Isian :attribute wajib ada jika :other bernilai :value.',
    'present_unless' => 'Isian :attribute wajib ada kecuali :other bernilai :value.',
    'present_with' => 'Isian :attribute wajib ada jika :values ada.',
    'present_with_all' => 'Isian :attribute wajib ada jika :values ada.',
    'prohibited' => 'Isian :attribute dilarang.',
    'prohibited_if' => 'Isian :attribute dilarang jika :other bernilai :value.',
    'prohibited_if_accepted' => 'Isian :attribute dilarang jika :other diterima.',
    'prohibited_if_declined' => 'Isian :attribute dilarang jika :other ditolak.',
    'prohibited_unless' => 'Isian :attribute dilarang kecuali :other ada di :values.',
    'prohibits' => 'Isian :attribute melarang :other untuk diisi.',
    'regex' => 'Format isian :attribute tidak valid.',
    'required' => 'Isian :attribute wajib diisi.',
    'required_array_keys' => 'Isian :attribute harus memuat entri untuk: :values.',
    'required_if' => 'Isian :attribute wajib diisi jika :other bernilai :value.',
    'required_if_accepted' => 'Isian :attribute wajib diisi jika :other diterima.',
    'required_if_declined' => 'Isian :attribute wajib diisi jika :other ditolak.',
    'required_unless' => 'Isian :attribute wajib diisi kecuali :other ada di :values.',
    'required_with' => 'Isian :attribute wajib diisi jika :values ada.',
    'required_with_all' => 'Isian :attribute wajib diisi jika :values ada.',
    'required_without' => 'Isian :attribute wajib diisi jika :values tidak ada.',
    'required_without_all' => 'Isian :attribute wajib diisi jika :values tidak ada sama sekali.',
    'same' => 'Isian :attribute harus sama dengan :other.',
    'size' => [
        'array' => 'Isian :attribute harus berisi :size item.',
        'file' => 'Isian :attribute harus berukuran :size kilobita.',
        'numeric' => 'Isian :attribute harus bernilai :size.',
        'string' => 'Isian :attribute harus berisi :size karakter.',
    ],
    'starts_with' => 'Isian :attribute harus diawali salah satu dari: :values.',
    'string' => 'Isian :attribute harus berupa teks.',
    'timezone' => 'Isian :attribute harus berupa zona waktu yang valid.',
    'unique' => ':attribute sudah digunakan.',
    'uploaded' => ':attribute gagal diunggah.',
    'uppercase' => 'Isian :attribute harus berhuruf kapital.',
    'url' => 'Isian :attribute harus berupa URL yang valid.',
    'ulid' => 'Isian :attribute harus berupa ULID yang valid.',
    'uuid' => 'Isian :attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa Validasi Kustom
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nama Atribut
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'nama',
        'email' => 'email',
        'password' => 'kata sandi',
        'current_password' => 'kata sandi saat ini',
    ],

];
