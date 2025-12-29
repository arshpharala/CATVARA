<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ setting('site_title', env('APP_NAME')) }}</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="{{ asset('theme/adminlte/plugins/fontawesome-free/css/all.min.css') }}">
  <!-- DataTables -->
  <link rel="stylesheet" href="{{ asset('theme/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
  <link rel="stylesheet"
    href="{{ asset('theme/adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ asset('theme/adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">

  <!-- Select2 -->
  <link rel="stylesheet" href="{{ asset('theme/adminlte/plugins/select2/css/select2.min.css') }}">
  <link rel="stylesheet"
    href="{{ asset('theme/adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

  <link rel="stylesheet" href="{{ asset('theme/adminlte/plugins/bs-stepper/css/bs-stepper.min.css') }}">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
  <link rel="stylesheet" href="{{ asset('theme/adminlte/plugins/daterangepicker/daterangepicker.css') }}">

  <!-- SweetAlert2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />

  <script>
    const appUrl = '{{ env('APP_URL') }}';
  </script>

  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Theme style -->
  <link rel="stylesheet" href="{{ asset('theme/adminlte/dist/css/adminlte.min.css') }}">

  <style>
    .tox-editor-header .tox-promotion-button {
      display: none !important;
    }

    .select2-selection__choice,
    .select2-results__option.select2-results__option--highlighted {
      background-color: #6c757d !important;
    }

  </style>

  @stack('head')

</head>

<body class="hold-transition sidebar-mini">
  <div class="wrapper">

    @include('theme.adminlte.layouts.navbar')
    @include('theme.adminlte.layouts.sidebar')

    <div class="content-wrapper">

      @hasSection('content-header')
        <div class="content-header">
          <div class="container-fluid">
            @yield('content-header')
          </div>
        </div>
      @endif


      <div class="content">
        <div class="container-fluid">
          @yield('content')
        </div>
      </div>
    </div>


    @include('theme.adminlte.layouts.footer')

    @include('theme.adminlte.layouts.right-sidebar')

  </div>



  <!-- jQuery -->
  <script src="{{ asset('theme/adminlte/plugins/jquery/jquery.min.js') }}"></script>
  <!-- Bootstrap 4 -->
  {{-- <script src="{{ asset('theme/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script> --}}

  <script src="{{ asset('theme/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  <!-- AdminLTE App -->
  <script src="{{ asset('theme/adminlte/dist/js/adminlte.min.js') }}"></script>
  <!-- DataTables  & Plugins -->
  <script src="{{ asset('theme/adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
  <script src="{{ asset('theme/adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
  <script src="{{ asset('theme/adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
  <script src="{{ asset('theme/adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
  <script src="{{ asset('theme/adminlte/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
  <script src="{{ asset('theme/adminlte/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
  <script src="{{ asset('theme/adminlte/plugins/jszip/jszip.min.js') }}"></script>
  <script src="{{ asset('theme/adminlte/plugins/pdfmake/pdfmake.min.js') }}"></script>
  <script src="{{ asset('theme/adminlte/plugins/pdfmake/vfs_fonts.js') }}"></script>
  <script src="{{ asset('theme/adminlte/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
  <script src="{{ asset('theme/adminlte/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
  <script src="{{ asset('theme/adminlte/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
  <!-- Select2 -->
  <script src="{{ asset('theme/adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
  <script src="{{ asset('theme/adminlte/plugins/bs-stepper/js/bs-stepper.min.js') }}"></script>

  <script src="https://cdn.tiny.cloud/1/{{ setting('tiny_mc_key', env('TINY_MC_KEY')) }}/tinymce/7/tinymce.min.js"
    referrerpolicy="origin"></script>
  <script>
    tinymce.init({
      selector: 'textarea.tinymce-editor',
      plugins: 'advlist autolink lists link image charmap preview anchor pagebreak table code fullscreen',
      toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code fullscreen',
      images_upload_url: "{{ route('admin.cms.upload.tinymce', ['_token' => csrf_token()]) }}",
      images_upload_credentials: true,
      height: 400,
      branding: false,
      relative_urls: false,
      remove_script_host: false,
      convert_urls: true,
      promotion: false
      // Use the default TinyMCE handler (no need for custom images_upload_handler!)
    });
  </script>

  <script>
    // setInterval(() => {
    //   fetch("{{ route('session.check') }}", {
    //       cache: "no-store"
    //     })
    //     .then(r => {
    //       if (!r.ok) location.reload();
    //     })
    //     .catch(() => location.reload());
    // }, 100000);
  </script>




  <!-- Custom JS -->
  <script src="{{ asset('theme/adminlte/assets/js/form.js') }}"></script>
  <script src="{{ asset('theme/adminlte/assets/js/navbar.js') }}"></script>
  <script src="{{ asset('theme/adminlte/assets/js/address.js') }}"></script>

  @stack('scripts')
</body>

</html>
