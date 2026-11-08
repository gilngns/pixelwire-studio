@extends('errors.layout', [
    'code' => '503',
    'kicker' => 'メンテナンス中',
    'title' => __('Back soon'),
    'message' => __('The site is being updated. Please check back in a few minutes.'),
])
