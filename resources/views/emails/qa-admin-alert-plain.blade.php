QA ADMIN NOTIFICATION
Quality Assurance Office - Holy Angel University

{{ $headline }}
[{{ $badge }}]

{{ $messageBody }}

@if(!empty($details) && count($details) > 0)
DETAILS:
----------------------------------------
@foreach($details as $label => $value)
{{ $label }}: {{ $value }}
@endforeach
----------------------------------------
@endif

@if(!empty($actionUrl))
View in QA Portal: {{ $actionUrl }}
@endif

--
Holy Angel University — Quality Assurance Portal
This is an automated system email. Please do not reply directly to this address.
