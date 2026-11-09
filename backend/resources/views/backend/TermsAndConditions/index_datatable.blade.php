@php
use App\Models\Term;
$terms = Term::all();
@endphp
@extends('backend.layouts.app')

@section('title', __('terms_conditions.page_title'))

@push('after-styles')
<style>
    .form-section {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .point-item {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
    }
    .point-item input {
        flex: 1;
    }
    .remove-btn {
        background: #dc2626;
        color: #fff;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
    }
    .add-btn {
        margin-top: 10px;
        background: #4f46e5;
        color: #fff;
        border: none;
        padding: 7px 15px;
        border-radius: 4px;
        cursor: pointer;
    }
</style>
@endpush

@section('content')
<div class="form-section">
    <form action="{{route('TermsAndConditions.store')}}" method="POST">
        @csrf

        {{-- عنوان السياسة --}}
        <div class="mb-3">
            <label class="form-label">{{ __('terms_conditions.title_ar') }}</label>
            <input type="text" name="title_ar" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('terms_conditions.title_en') }}</label>
            <input type="text" name="title_en" class="form-control" required>
        </div>

        {{-- النقاط --}}
        <div class="mb-3">
            <label class="form-label">{{ __('terms_conditions.points') }}</label>
            <div id="points-wrapper">
                <div class="point-item">
                    <input type="text" name="points[0][ar]" placeholder="{{ __('terms_conditions.point_ar_placeholder') }}" class="form-control" required>
                    <input type="text" name="points[0][en]" placeholder="{{ __('terms_conditions.point_en_placeholder') }}" class="form-control" required>
                    <button type="button" class="remove-btn" onclick="removePoint(this)">X</button>
                </div>
            </div>
            @hasPermission('add_terms_and_conditions')
            <button type="button" class="add-btn" onclick="addPoint()">+ {{ __('terms_conditions.add_new_point') }}</button>
            @endhasPermission
        </div>
        @hasPermission('add_terms_and_conditions')
        <button type="submit" class="btn btn-primary">{{ __('terms_conditions.save_policy') }}</button>
        @endhasPermission
    </form>
</div>

<div class="form-section" style="overflow: auto;">
    <h4 class="mb-3">{{ __('terms_conditions.terms_list') }}</h4>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('terms_conditions.title_ar') }}</th>
                <th>{{ __('terms_conditions.title_en') }}</th>
                <th>{{ __('terms_conditions.points') }}</th>
                <th>{{ __('terms_conditions.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($terms as $index => $term)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $term->title['ar'] ?? '' }}</td>
                    <td>{{ $term->title['en'] ?? '' }}</td>
                    <td>
                        <ul>
                            @foreach($term->points['ar'] ?? [] as $pIndex => $pointAr)
                                <li>{{ $pointAr }} <br> {{ $term->points['en'][$pIndex] ?? '' }}</li>
                            @endforeach
                        </ul>
                    </td>
                    <td>
                        @hasPermission('edit_terms_and_conditions')
                        <button type="button" 
                            class="btn btn-sm btn-warning"
                            data-bs-toggle="modal"
                            data-bs-target="#editModal"
                            data-id="{{ $term->id }}"
                            data-title-ar="{{ $term->title['ar'] ?? '' }}"
                            data-title-en="{{ $term->title['en'] ?? '' }}"
                            data-points='@json($term->points)'>
                            {{ __('terms_conditions.edit') }}
                        </button>
                        @endhasPermission
                        @hasPermission('delete_terms_and_conditions')
                        <form action="{{ route('TermsAndConditions.destroy', $term->id) }}" method="POST" style="display:inline-block">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('terms_conditions.confirm_delete') }}')">{{ __('terms_conditions.delete') }}</button>
                        </form>
                        @endhasPermission
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">{{ __('terms_conditions.no_terms') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <!-- نافذة التعديل -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editForm" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">{{ __('terms_conditions.edit_policy') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('terms_conditions.close') }}"></button>
        </div>
        <div class="modal-body">
          
          {{-- العنوان --}}
          <div class="mb-3">
              <label class="form-label">{{ __('terms_conditions.title_ar') }}</label>
              <input type="text" id="edit_title_ar" name="title_ar" class="form-control" required>
          </div>
          <div class="mb-3">
              <label class="form-label">{{ __('terms_conditions.title_en') }}</label>
              <input type="text" id="edit_title_en" name="title_en" class="form-control" required>
          </div>

          {{-- النقاط --}}
          <div class="mb-3">
              <label class="form-label">{{ __('terms_conditions.points') }}</label>
              <div id="edit_points_wrapper"></div>
              <button type="button" class="add-btn" id="addEditPoint">+ {{ __('terms_conditions.add_new_point') }}</button>
          </div>
          
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('terms_conditions.cancel') }}</button>
          <button type="submit" class="btn btn-primary">{{ __('terms_conditions.save_changes') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

</div>
@endsection

@push('after-scripts')
<script>
    let pointIndex = 1;

    function addPoint() {
        const wrapper = document.getElementById('points-wrapper');
        const div = document.createElement('div');
        div.classList.add('point-item');
        div.innerHTML = `
            <input type="text" name="points[${pointIndex}][ar]" placeholder="{{ __('terms_conditions.point_ar_placeholder') }}" class="form-control" required>
            <input type="text" name="points[${pointIndex}][en]" placeholder="{{ __('terms_conditions.point_en_placeholder') }}" class="form-control" required>
            <button type="button" class="remove-btn" onclick="removePoint(this)">X</button>
        `;
        wrapper.appendChild(div);
        pointIndex++;
    }

    function removePoint(button) {
        button.parentElement.remove();
    }
</script>
<script>
  let editPointIndex = 0;

  const editModal = document.getElementById('editModal');
  editModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;

    // البيانات من زرار التعديل
    const id = button.getAttribute('data-id');
    const titleAr = button.getAttribute('data-title-ar');
    const titleEn = button.getAttribute('data-title-en');
    const points = JSON.parse(button.getAttribute('data-points'));
    // تعبئة الفورم
    document.getElementById('edit_title_ar').value = titleAr;
    document.getElementById('edit_title_en').value = titleEn;

    // إعداد الرابط للفورم
    document.getElementById('editForm').action = `/TermsAndConditions/${id}/update`;

    // إعادة ضبط النقاط
    const wrapper = document.getElementById('edit_points_wrapper');
    wrapper.innerHTML = '';
    editPointIndex = 0;

    if(points && points.ar) {
      points.ar.forEach((pAr, index) => {
        const pEn = points.en[index] ?? '';
        wrapper.insertAdjacentHTML('beforeend', `
          <div class="point-item">
              <input type="text" name="points[${editPointIndex}][ar]" value="${pAr}" class="form-control" required>
              <input type="text" name="points[${editPointIndex}][en]" value="${pEn}" class="form-control" required>
              <button type="button" class="remove-btn" onclick="this.parentElement.remove()">X</button>
          </div>
        `);
        editPointIndex++;
      });
    }
  });

  // زرار إضافة نقطة جديدة داخل المودال
  document.getElementById('addEditPoint').addEventListener('click', function () {
    const wrapper = document.getElementById('edit_points_wrapper');
    wrapper.insertAdjacentHTML('beforeend', `
      <div class="point-item">
          <input type="text" name="points[${editPointIndex}][ar]" placeholder="{{ __('terms_conditions.point_ar_placeholder') }}" class="form-control" required>
          <input type="text" name="points[${editPointIndex}][en]" placeholder="{{ __('terms_conditions.point_en_placeholder') }}" class="form-control" required>
          <button type="button" class="remove-btn" onclick="this.parentElement.remove()">X</button>
      </div>
    `);
    editPointIndex++;
  });
</script>

@endpush
