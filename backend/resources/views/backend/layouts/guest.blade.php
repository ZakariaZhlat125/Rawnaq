<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ language_direction() }}" class="theme-fs-sm">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <link rel="icon" type="image/png" href="{{ asset('images/JOSPA.webp') }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('images/JOSPA.webp') }}">
    <meta name="keyword" content="{{ setting('meta_keyword') }}">
    <meta name="description" content="{{ setting('meta_description') }}">
    <meta name="setting_options" content="{{ setting('customization_json') }}">

    <!-- Shortcut Icon -->
    <link rel="shortcut icon" href="{{ asset('images/JOSPA.webp') }}">
    <link rel="icon" type="image/ico" href="{{ asset('images/JOSPA.webp') }}" />

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app_name" content="{{ app_name() }}">

    <title>@yield('title') | {{ app_name() }}</title>

    <link rel="stylesheet" href="{{ mix('css/icon.min.css') }}">
    @stack('before-styles')
    <link rel="stylesheet" href="{{ mix('css/libs.min.css') }}">
    <link rel="stylesheet" href="{{ mix('css/backend.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dark.css') }}">
    <link rel="stylesheet" href="{{ asset('custom-css/dashboard.css') }}">

    @if(language_direction() == 'rtl')
      <link rel="stylesheet" href="{{ asset('css/rtl.css') }}">
    @endif

    <link rel="stylesheet" href="{{ asset('css/customizer.css') }}">


    <style>
        :root{
          <?php
            $rootColors = setting('root_colors'); // Assuming the setting() function retrieves the JSON string

            // Check if the JSON string is not empty and can be decoded
            if (!empty($rootColors) && is_string($rootColors)) {
                $colors = json_decode($rootColors, true);

                // Check if decoding was successful and the colors array is not empty
                if (json_last_error() === JSON_ERROR_NONE && is_array($colors) && count($colors) > 0) {
                    foreach ($colors as $key => $value) {
                        echo $key . ': ' . $value . '; ';
                    }
                } else {
                    echo 'Invalid JSON or empty colors array.';
                }
            }
            ?>

        }
        
        :root {
            --primary-color: #bf9456 !important;
            --primary: #bf9456 !important;
            --secondary: #bf9456 !important;
            --info: #bf9456 !important;
            --success: #bf9456 !important;
            --warning: #bf9456 !important;
            --danger: #bf9456 !important;
            --light: #bf9456 !important;
            --dark: #bf9456 !important;
        }
        
        .btn-primary,
        .btn-secondary,
        .btn-info,
        .btn-success,
        .btn-warning,
        .btn-danger {
            background-color: #bf9456 !important;
            border-color: #bf9456 !important;
            color: white !important;
        }
        
        .btn-primary:hover,
        .btn-secondary:hover,
        .btn-info:hover,
        .btn-success:hover,
        .btn-warning:hover,
        .btn-danger:hover {
            background-color: #a8834b !important;
            border-color: #a8834b !important;
        }
        
        a {
            color: #bf9456 !important;
        }
        
        a:hover {
            color: #bf9456 !important;
        }
        
        .text-primary,
        .text-secondary,
        .text-info,
        .text-success,
        .text-warning,
        .text-danger {
            color: #bf9456 !important;
        }
        
        .border-primary,
        .border-secondary,
        .border-info,
        .border-success,
        .border-warning,
        .border-danger {
            border-color: #bf9456 !important;
        }
        
        .bg-primary,
        .bg-secondary,
        .bg-info,
        .bg-success,
        .bg-warning,
        .bg-danger {
            background-color: #bf9456 !important;
        }
        
        .icon-primary,
        .icon-secondary,
        .icon-info,
        .icon-success,
        .icon-warning,
        .icon-danger {
            color: #bf9456 !important;
        }
        
        input:focus,
        textarea:focus,
        select:focus,
        .form-control:focus,
        .form-select:focus {
            border-color: #bf9456 !important;
            box-shadow: 0 0 0 0.2rem rgba(191, 148, 86, 0.25) !important;
            outline: none !important;
        }
        
        .table-primary,
        .table-secondary,
        .table-info,
        .table-success,
        .table-warning,
        .table-danger {
            background-color: #bf9456 !important;
            color: white !important;
        }
        
        .card-primary,
        .card-secondary,
        .card-info,
        .card-success,
        .card-warning,
        .card-danger {
            border-color: #bf9456 !important;
        }
        
        .card-header-primary,
        .card-header-secondary,
        .card-header-info,
        .card-header-success,
        .card-header-warning,
        .card-header-danger {
            background-color: #bf9456 !important;
            color: white !important;
        }
        
        .alert-primary,
        .alert-secondary,
        .alert-info,
        .alert-success,
        .alert-warning,
        .alert-danger {
            background-color: #bf9456 !important;
            border-color: #bf9456 !important;
            color: white !important;
        }
        
        .list-group-item-primary,
        .list-group-item-secondary,
        .list-group-item-info,
        .list-group-item-success,
        .list-group-item-warning,
        .list-group-item-danger {
            background-color: #bf9456 !important;
            color: white !important;
        }
        
        .badge-primary,
        .badge-secondary,
        .badge-info,
        .badge-success,
        .badge-warning,
        .badge-danger {
            background-color: #bf9456 !important;
            color: white !important;
        }
        
        .progress-bar-primary,
        .progress-bar-secondary,
        .progress-bar-info,
        .progress-bar-success,
        .progress-bar-warning,
        .progress-bar-danger {
            background-color: #bf9456 !important;
        }
        
        .dropdown-item:hover {
            background-color: #bf9456 !important;
            color: white !important;
        }
        
        .nav-link.active,
        .nav-link:hover {
            color: #bf9456 !important;
        }
        
        .nav-pills .nav-link.active {
            background-color: #bf9456 !important;
            color: white !important;
        }
        
        .nav-tabs .nav-link.active {
            border-color: #bf9456 !important;
            color: #bf9456 !important;
        }
        
        .form-check-input:checked {
            background-color: #bf9456 !important;
            border-color: #bf9456 !important;
        }
        
        .sidebar-menu .nav-link:hover,
        .sidebar-menu .nav-link.active {
            color: #bf9456 !important;
        }
        
        .btn-sm,
        .btn-lg {
            background-color: #bf9456 !important;
            border-color: #bf9456 !important;
            color: white !important;
        }
        
        .table a {
            color: #bf9456 !important;
        }
        
        .table .icon {
            color: #bf9456 !important;
        }
    </style>

    <!-- Scripts -->
    @php
        $currentLang = App::currentLocale();
        $langFolderPath = base_path("lang/$currentLang");
        $filePaths = \File::files($langFolderPath);
      @endphp

    @include('backend.includes.js-json-translations')
    @foreach ($filePaths as $filePath)
      @php
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);

        $arr = require($filePath);
        $dbLang = Modules\Language\Models\Language::getAllLang()->where('language', app()->getLocale())
            ->where('file', $fileName)
            ->pluck('value', 'key')
            ->toArray();
            if(count($dbLang) > 0) {
              $arr = array_merge($arr, $dbLang);
            }
      @endphp
      <script>
        window.localMessagesUpdate = {
          ...window.localMessagesUpdate,
          "{{ $fileName }}": @json($arr)
        }
      </script>
    @endforeach


    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: Inter, Arial, Helvetica, sans-serif
        }
    </style>

    @stack('after-styles')

    <style>
      {!! setting('custom_css_block') !!}
    </style>

</head>

<body>
    <!-- Loader Start -->
    <div id="loading">
        <x-partials._body_loader />
    </div>
    <!-- Loader End -->
    <div class="main-content wrapper">
        <div class="conatiner-fluid content-inner pb-0" id="page_layout" data-render="app">

            @include('flash::message')

            <!-- Errors block -->
            @include('backend.includes.errors')
            <!-- / Errors block -->

            <!-- Main content block -->
            @yield('content')
            <!-- / Main content block -->
        </div>
    </div>

    <!-- Scripts -->
    @stack('before-scripts')
    <script src="{{ mix('js/backend.js') }}"></script>
    <script src="{{ asset('js/app.js') }}" defer></script>

    @if (isset($assets) && (in_array('textarea', $assets) || in_array('editor', $assets)))
        <script src="{{ asset('vendor/tinymce/js/tinymce/tinymce.min.js') }}"></script>
        <script src="{{ asset('vendor/tinymce/js/tinymce/jquery.tinymce.min.js') }}"></script>
    @endif

    @stack('after-scripts')
    <!-- / Scripts -->

    <script>
      {!! setting('custom_js_block') !!}
    </script>

</body>

</html>
