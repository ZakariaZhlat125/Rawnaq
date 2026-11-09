@extends('backend.layouts.app')

@section('title')
    {{ __('messages.order_categories') }}
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h4 class="mb-0">{{ __('messages.order_categories') }}</h4>
                <button id="save-order" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> {{ __('messages.save_order') }}
                </button>
            </div>

            <p class="text-muted mb-3">{{ __('messages.drag_to_order') }}</p>

            <ul id="categories-sortable" class="list-group">
                @foreach($categories as $category)
                    <li class="list-group-item d-flex align-items-center justify-content-between" data-id="{{ $category->id }}">
                        <span class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-grip-vertical text-muted"></i>
                            {{ $category->name }}
                        </span>
                        <span class="badge bg-light text-dark">#{{ $loop->iteration }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection

@push('after-scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        const list = document.getElementById('categories-sortable');
        const saveBtn = document.getElementById('save-order');

        const sortable = new Sortable(list, {
            animation: 150,
            handle: '.fa-grip-vertical'
        });

        saveBtn.addEventListener('click', () => {
            const ids = Array.from(list.querySelectorAll('[data-id]')).map(el => el.dataset.id);
            fetch('{{ route("backend.categories.order.update") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ ids })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    if (typeof window.successSnackbar === 'function') {
                        window.successSnackbar(data.message);
                    }
                } else {
                    if (typeof window.errorSnackbar === 'function') {
                        window.errorSnackbar(data.message);
                    } else {
                        alert(data.message || 'Error');
                    }
                }
            })
            .catch(() => {
                alert('Error');
            });
        });
    </script>
@endpush
