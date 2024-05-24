@extends('layouts.master')

@section('manageUsers')
<div class="container p-4">
    <div class="text-center">
        <h1 class="">Manage All Business Accounts</h1>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-md btn-primary">Add Account</a>

    @include('partials.allUsersPagination')
    @include('partials.allUsersTbl')
    @include('partials.allUsersModalImagePreview')

</div>

<!-- Back to Top Button -->
<button onclick="topFunction()" id="backToTopBtn" title="Go to top"></button>

@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/allusers.css') }}">
<style>
    *{
        font-family: 'Bebas Neue', sans-serif;    }
</style>
@endsection

@section('scripts')
<!-- Include jQuery and DataTables JavaScript libraries -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.0.0/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.0.0/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.0.0/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.0.0/js/buttons.colVis.min.js"></script>

<script>
    $(document).ready(function() {
        // Get the current limit parameter from the URL
        var defaultLimit = {{ request()->input('limit', 10) }};
        // Get the current sort parameter from the URL
        var defaultSort = "{{ request()->input('sort', 'newest') }}";

        // DataTable initialization
        var table = $('#example').DataTable({
            "dom": '<"d-flex justify-content-between align-items-center"Bf><"clear">lirtp',
            "paging": true,
            "lengthMenu": [[5, 10, 20, 50, 100, -1], [5, 10, 20, 50, 100, "All"]],
            "pageLength": defaultLimit, // Set the default page length
            "autoWidth": true,
            "language": {
                "info": "Showing _START_ to _END_ of _TOTAL_ entries"
            },
            "order": [],
            "buttons": [
                {
                    extend: 'colvis',
                    text: 'Columns',
                    className: 'btn btn-secondary dropdown-toggle',
                    attr: {
                        'aria-haspopup': true,
                        'aria-expanded': false,
                        'data-toggle': 'dropdown'
                    },
                    dropdown: {
                        className: 'dropdown-menu dropdown-menu-right'
                    }
                },
                {
                    extend: 'excelHtml5',
                    className: 'btn btn-secondary'
                },
                {
                    extend: 'pdfHtml5',
                    className: 'btn btn-secondary'
                },

            ]
        });

        // Set the default sorting
        table.order([0, defaultSort === 'newest' ? 'desc' : 'asc']).draw();

    });


    // Back to Top Button Functionality
    window.onscroll = function() {scrollFunction()};

    function scrollFunction() {
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            document.getElementById("backToTopBtn").style.display = "block";
        } else {
            document.getElementById("backToTopBtn").style.display = "none";
        }
    }

    function topFunction() {
        document.body.scrollTop = 0;
        document.documentElement.scrollTop = 0;
    }

</script>
@endsection
