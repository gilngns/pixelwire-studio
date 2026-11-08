<?php

/*
| Hanya bagian upload yang diubah, supaya video demo portofolio sampai 50 MB
| bisa diupload dari admin. Nilai lain memakai default Livewire.
| Pastikan upload_max_filesize dan post_max_size di php.ini juga minimal 64M.
*/

return [
    'temporary_file_upload' => [
        'disk' => null,
        'rules' => ['required', 'file', 'max:51200'],
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],
];
