<h2>Hello</h2> <br><br>
You have got an email from : {{ $first_name }} <br><br>

User details: <br><br>

{{ __('Name:') }} {{ $first_name }} {{ $last_name }} <br>
{{ __('Email:') }} {{ $email }} <br>
{{ __('Phone:') }} {{ $phone_no }} <br>
Subject: {{ $subject }} <br>
Message: {{ $message }} <br><br>