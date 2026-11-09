@extends('backend.layouts.app')

@section('title')
    {{ __($module_action) }} {{ __($module_title) }}
@endsection


@push('after-styles')
    <link rel="stylesheet" href="{{ mix('modules/constant/style.css') }}">
@endpush
@section('content')
    <div class="mb-3"><a class="btn btn-outline-primary" href="{{ route('app.booking-reviews') }}">تقييمات الحجوزات والخدمات والموظفين</a></div>
    <!-- Employee Ratings Statistics -->
    @php
        $ratingSummaries = app(\App\Services\EmployeeReviewSummary::class)->summaries(\App\Models\User::role('employee')->branch()->pluck('users.id')->all());
        $employeeStats = collect($ratingSummaries);
        $totalReviews = $employeeStats->sum('count');
        $overallAvg = $totalReviews ? $employeeStats->sum('sum') / $totalReviews : 0;
    @endphp
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ __('employee.overall_rating') }}</h5>
                    <h2 class="mb-0">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= round($overallAvg))
                                <i class="fa-solid fa-star text-warning"></i>
                            @else
                                <i class="fa-regular fa-star"></i>
                            @endif
                        @endfor
                        <span class="ms-2">{{ number_format($overallAvg, 1) }}</span>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ __('employee.total_reviews') }}</h5>
                    <h2 class="mb-0">{{ $totalReviews }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ __('employee.rated_employees') }}</h5>
                    <h2 class="mb-0">{{ $employeeStats->count() }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <x-backend.section-header>
                <div class="d-flex flex-wrap gap-3">
                    @if (auth()->user()->can('edit_staff') || auth()->user()->can('delete_staff'))
                        <x-backend.quick-action url='{{ route("backend.$module_name.bulk_action") }}'>
                            <div class="">
                                <select name="action_type" class="form-control select2 col-12" id="quick-action-type"
                                    style="width:100%">
                                    <option value="">{{ __('messages.no_action') }}</option>
                                    @can('edit_staff')
                                        <option value="change-status">{{ __('messages.status') }}</option>
                                    @endcan
                                    @can('delete_staff')
                                        <option value="delete">{{ __('messages.delete') }}</option>
                                    @endcan
                                </select>
                            </div>
                            <div class="select-status d-none quick-action-field" id="change-status-action">
                                <select name="status" class="form-control select2" id="status" style="width:100%">
                                    <option value="1">{{ __('messages.active') }}</option>
                                    <option value="0">{{ __('messages.inactive') }}</option>
                                </select>
                            </div>
                        </x-backend.quick-action>
                    @endif
                    <div>
                        <button type="button" class="btn btn-secondary" data-modal="export">
                            <i class="fa-solid fa-download"></i> {{ __('messages.export') }}
                        </button>
                        {{--          <button type="button" class="btn btn-secondary" data-modal="import"> --}}
                        {{--            <i class="fa-solid fa-upload"></i> Import --}}
                        {{--          </button> --}}
                    </div>
                </div>
                <x-slot name="toolbar">
                    <select id="employee-branch-filter" class="form-select" aria-label="{{ __('branch.title') }}">
                        <option value="">{{ __('messages.all') }} - {{ __('branch.title') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <div class="input-group flex-nowrap">
                        <span class="input-group-text" id="addon-wrapping"><i
                                class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control dt-search" placeholder="{{ __('messages.search') }}..."
                            aria-label="{{ __('Search') }}" aria-describedby="addon-wrapping">
                    </div>
                    @hasPermission('add_staff')
                        <x-buttons.offcanvas target='#form-offcanvas'
                            title="">{{ __('messages.new') }}
                        </x-buttons.offcanvas>
                    @endhasPermission
                </x-slot>
            </x-backend.section-header>
            <table id="datatable" class="table table-striped border table-responsive">
            </table>
        </div>
    </div>

    <div data-render="app" class="{{ $selected_branch_id }}">
        <employee-offcanvas :selected-session-branch-id="{{ $selected_branch_id !== '' ? $selected_branch_id : null }}"
            default-image="{{ default_user_avatar() }}"
            create-title="{{ __('messages.new') }} {{ __('employee.singular_title') }} "
            edit-title="{{ __('messages.edit') }} {{ __('employee.singular_title') }}"
            :customefield="{{ json_encode($customefield) }}">
        </employee-offcanvas>
        <change-password create-title="{{ __('messages.change_password') }} "></change-password>
        <service-view-offcanvas></service-view-offcanvas>
    </div>
@endsection

@push('after-styles')
    <!-- DataTables Core and Extensions -->
    <link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush

@push('after-scripts')
    <script src="{{ mix('modules/employee/script.js') }}"></script>
    <script src="{{ asset('js/form-offcanvas/index.js') }}" defer></script>
    <script src="{{ asset('js/form-modal/index.js') }}" defer></script>

    <!-- DataTables Core and Extensions -->
    <script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>

    <script type="text/javascript" defer>
        const columns = [{
                name: 'check',
                data: 'check',
                title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
                width: '0%',
                exportable: false,
                orderable: false,
                searchable: false,
            },
            {
            data: 'employee_id',
            name: 'employee_id',
             title: "{{ __('booking.lbl_staff_name') }}"    
            },
            // {
            //     data: 'image',
            //     name: 'image',
            //     title: "{{ __('employee.lbl_image') }}",
            //     orderable: false,
            //     searchable: false
            // },
            // {
            //     data: 'first_name',
            //     name: 'first_name',
            //     title: "{{ __('employee.lbl_first_name') }}"
            // },
            // {
            //     data: 'last_name',
            //     name: 'last_name',
            //     title: "{{ __('employee.lbl_last_name') }}"
            // },
            // {
            //     data: 'email',
            //     name: 'email',
            //     title: "{{ __('employee.lbl_Email') }}"
            // },
            {
                data: 'service',
                name: 'service',
                title: "{{ __('employee.lbl_service') }}",
                orderable: false,
                searchable: false
            },
            {
                data: 'branch_id',
                name: 'branch_id',
                title: "{{ __('branch.title') }}",
                orderable: false,
                searchable: false
            },
            {
                data: 'average_rating',
                name: 'average_rating',
                title: 'متوسط تقييم العملاء (العدد)',
                orderable: false,
                searchable: false
            },
            {
                data: 'shift_id',
                name: 'shift_id',
                title: "{{ __('employee.lbl_shift') }}",
                orderable: false,
                searchable: false
            },
            {
                data: 'is_manager',
                name: 'is_manager',
                title: "{{ __('employee.lbl_role') }}"
            },
            {
                data: 'email_verified_at',
                name: 'email_verified_at',
                orderable: true,
                searchable: false,
                title: "{{ __('employee.lbl_verification_status') }}"
            },
            {
                data: 'wallet_balance',
                name: 'wallet_balance',
                orderable: false,
                searchable: true,
                title: "{{ __('customer.wallet_balance') }}"
            },
            {
                data: 'created_at',
                name: 'created_at',
                title: "{{ __('customer.lbl_create_at') }}",
                orderable: true,
                visible: false,
            },

            // {
            //     data: 'is_banned',
            //     name: 'is_banned',
            //     orderable: true,
            //     searchable: true,
            //     title: "{{ __('employee.lbl_blocked') }}"
            // },
            {
                data: 'status',
                name: 'status',
                orderable: true,
                searchable: true,
                title: "{{ __('employee.lbl_status') }}"
            },
            {
                data: 'updated_at',
                name: 'updated_at',
                title: "{{ __('customer.lbl_update_at') }}",
                orderable: true,
                visible: false,
            },
        ]

        const actionColumn = [{
            data: 'action',
            name: 'action',
            orderable: false,
            searchable: false,
             title: "{{ __('employee.lbl_action') }}"
       }]

        const customFieldColumns = JSON.parse(@json($columns))

        let finalColumns = [
            ...columns,
            ...customFieldColumns,
            ...actionColumn
        ]

        document.addEventListener('DOMContentLoaded', (event) => {
            initDatatable({
                url: '{{ route("backend.$module_name.index_data") }}',
                finalColumns,
                advanceFilter: () => ({ branch_id: $('#employee-branch-filter').val() }),
                orderColumn: [
                    [1, "asc"]
                ],
            })
            $('#employee-branch-filter').on('change', function () {
                $('#datatable').DataTable().ajax.reload();
            });
        })

        function resetQuickAction() {
            const actionValue = $('#quick-action-type').val();
            if (actionValue != '') {
                $('#quick-action-apply').removeAttr('disabled');

                if (actionValue == 'change-status') {
                    $('.quick-action-field').addClass('d-none');
                    $('#change-status-action').removeClass('d-none');
                } else {
                    $('.quick-action-field').addClass('d-none');
                }
            } else {
                $('#quick-action-apply').attr('disabled', true);
                $('.quick-action-field').addClass('d-none');
            }
        }

        $('#quick-action-type').change(function() {
            resetQuickAction()
        });

        $(document).on('update_quick_action', function() {
            // resetActionButtons()
        })
    </script>
@endpush
