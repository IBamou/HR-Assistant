@props(['title' => null])

@include('layouts.app.sidebar', ['title' => $title, 'slot' => $slot])
