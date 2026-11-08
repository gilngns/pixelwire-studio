@extends('errors.layout', [
    'code' => '429',
    'kicker' => 'ちょっと待って',
    'title' => __('Too many tries'),
    'message' => __('That was sent too many times in a short time. Wait a few minutes, then try again.'),
])
