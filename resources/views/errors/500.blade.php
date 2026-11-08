@extends('errors.layout', [
    'code' => '500',
    'kicker' => 'エラー',
    'title' => __('Something went wrong'),
    'message' => __('There was a problem on the server. Please try again in a moment.'),
])
