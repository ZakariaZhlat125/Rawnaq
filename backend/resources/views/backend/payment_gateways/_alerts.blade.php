@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger mb-3">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif
