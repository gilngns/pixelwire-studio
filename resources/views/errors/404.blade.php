@extends('errors.layout', [
    'code' => '404',
    'kicker' => 'ページが見つかりません',
    'title' => __('Page not found'),
    'message' => __('This page does not exist or has moved. Check the link, or start from the home page.'),
])

@section('actions')
    <a href="{{ route('services') }}" class="btn btn-primary">{{ __('Services & prices') }}</a>
@endsection
