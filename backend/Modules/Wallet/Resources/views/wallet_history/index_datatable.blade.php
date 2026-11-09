@extends('backend.layouts.app')

@section('title')
{{ __($module_action) }} {{ __($module_title) }}
@endsection
@push('after-styles')
<link rel="stylesheet" href="{{ mix('modules/tax/style.css') }}">
@endpush


@section('content')
<div class="card">
    <div class="card-body">
            <x-backend.section-header>
                <x-slot name="toolbar">
                    <div class="input-group flex-nowrap">
                    <span class="input-group-text" id="addon-wrapping"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="form-control dt-search" placeholder="{{ __('messages.search') }}..." aria-label="{{ __('Search') }}" aria-describedby="addon-wrapping">
                    </div>
                </x-slot>
            </x-backend.section-header>
            <table id="datatable" class="table border table-responsive">
            </table>
        </div>
    </div>
</div>
@endsection

@push('after-styles')
<!-- DataTables Core and Extensions -->
<link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush

@push('after-scripts')
<!-- DataTables Core and Extensions -->
<script src="{{ mix('modules/tax/script.js') }}"></script>
<script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>

<script type="text/javascript">
    const finalColumns = [ 
        {
            data: 'datetime',
            name: 'datetime',
            title: "{{ __('messages.date') }}",
            width: '15%',
        },
        {
            data: 'activity_type',
            name: 'activity_type',
            title: "{{ __('messages.type') }}",
            width: '15%'
        },
        {
            data: 'activity_message',
            name: 'activity_message',
            title: "{{ __('messages.messages') }}",
            width: '15%',
        },
        {
            data: 'amount',
            name: 'amount',
            title: "{{ __('messages.amount') }}",
            width: '5%',
        },
        {
            data: 'updated_at',
            name: 'updated_at',
            title: "{{ __('tax.lbl_updated') }}",
            width: '5%',
            visible: false,
        },
    ]
    document.addEventListener('DOMContentLoaded', (event) => {
        initDatatable({
            url: '{{ route("wallet.history-data", ["id" => $user_id]) }}',
            finalColumns,
            orderColumn: [[ 4, "desc" ]],
        })
    })


    const formOffcanvas = document.getElementById('form-offcanvas')

    const instance = bootstrap.Offcanvas.getOrCreateInstance(formOffcanvas)

    $(document).on('click', '[data-crud-id]', function() {
        setEditID($(this).attr('data-crud-id'), $(this).attr('data-parent-id'))
    })

    function setEditID(id, parent_id) {
        if (id !== '' || parent_id !== '') {
            const idEvent = new CustomEvent('crud_change_id', {
                detail: {
                    form_id: id,
                    parent_id: parent_id
                }
            })
            document.dispatchEvent(idEvent)
        } else {
            removeEditID()
        }
        instance.show()
    }

    function removeEditID() {
        const idEvent = new CustomEvent('crud_change_id', {
            detail: {
                form_id: 0,
                parent_id: null
            }
        })
        document.dispatchEvent(idEvent)
    }

    formOffcanvas?.addEventListener('hidden.bs.offcanvas', event => {
        removeEditID()
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
</script>
@endpush