@component('mail::message')
![logo](https://api.wenueat.com/public/storage/logo.png)
# Hello {{$name}},
You have a new order.<br>
Please login to Wenueat to check it.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
