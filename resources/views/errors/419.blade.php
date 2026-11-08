@extends('errors.layout', [
    'code' => '419',
    'kicker' => 'セッション切れ',
    'title' => __('This page expired'),
    'message' => __('The form was open for too long, so it could not be sent. Go back, refresh the page and send it again.'),
])

@section('actions')
    <a href="{{ url()->previous() }}" class="btn btn-primary">{{ __('Go back') }}</a>
@endsection
