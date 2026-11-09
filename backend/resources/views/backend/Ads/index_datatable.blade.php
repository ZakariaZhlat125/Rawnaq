@extends('backend.layouts.app')

@section('title')

@endsection

@push('after-styles')
<style>
    h2 {
        color: #BF9456;
        margin-bottom: 15px;
        font-size: 1.5rem;
        border-bottom: 2px solid #BF9456;
        padding-bottom: 5px;
    }

    .section {
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }

    .upload-btn {
        display: inline-block;
        padding: 10px 20px;
        margin-bottom: 15px;
        border-radius: 5px;
        background: #BF9456;
        color: #fff;
        font-weight: bold;
        cursor: pointer;
        border: none;
    }

    .upload-btn:hover {
        background: #a67c45;
    }

    input[type="file"] { display: none; }

    .image-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 15px;
    }

    .image-card {
        width: 150px;
        border: 1px solid #ddd;
        border-radius: 5px;
        overflow: hidden;
        background: #fafafa;
        padding: 5px;
        display: flex;
        flex-direction: column;
        align-items: center;
        font-size: 14px;
    }

    .image-card img {
        width: 100%;
        height: 100px;
        object-fit: cover;
        margin-bottom: 5px;
        border-radius: 3px;
    }

    .image-actions {
        display: flex;
        justify-content: space-between;
        width: 100%;
    }

    .image-actions button {
        padding: 5px 10px;
        font-size: 12px;
        border: none;
        border-radius: 3px;
        cursor: pointer;
    }

    .btn-toggle {
        background: #4CAF50;
        color: #fff;
    }

    .btn-toggle.inactive {
        background: #f44336;
    }

    .btn-delete {
        background: #888;
        color: #fff;
    }
    .modal {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    }
    
    .modal-content {
        background: #fff;
        padding: 20px;
        width: 350px;
        border-radius: 8px;
    }
    
    .modal-content input {
        width: 100%;
        padding: 8px;
        margin: 10px 0;
    }
    
    .modal-actions {
        display: flex;
        justify-content: space-between;
    }
    
    .icon-btn {
        background: linear-gradient(135deg, #CF9233, #FFD699);
        border: none;
        border-radius: 12px;
        padding: 10px 12px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: 0.3s;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .icon-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    }
    
    .icon-btn .iconify {
        color: white;
    }

    .modal {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.6);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        animation: fadeIn 0.3s ease forwards;
    }
    
    .modal-content {
        background: #fff;
        padding: 25px 30px;
        width: 400px;
        max-width: 90%;
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        display: flex;
        flex-direction: column;
        gap: 20px;
        animation: slideIn 0.3s ease forwards;
    }
    
    .modal-content h4 {
        margin: 0;
        font-size: 1.4rem;
        color: #BF9456;
        text-align: center;
    }
    
    .modal-content input {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
        transition: 0.3s;
    }
    
    .modal-content input:focus {
        border-color: #BF9456;
        outline: none;
        box-shadow: 0 0 8px rgba(191,148,86,0.4);
    }
    
    /* أزرار المودال */
    .modal-actions {
        display: flex;
        justify-content: space-between;
        gap: 10px;
    }
    
    /* زر الحفظ */
    .btn-save {
        flex: 1;
        background: linear-gradient(135deg, #BF9456, #FFD699);
        color: white;
        padding: 10px 0;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        transition: 0.3s;
    }
    
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(191,148,86,0.4);
    }
    
    /* زر الإلغاء */
    .btn-cancel {
        flex: 1;
        background: #eee;
        color: #555;
        padding: 10px 0;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        transition: 0.3s;
    }
    
    .btn-cancel:hover {
        background: #ddd;
    }
    
    /* تأثير دخول المودال */
    @keyframes fadeIn {
        from {opacity: 0;}
        to {opacity: 1;}
    }
    
    @keyframes slideIn {
        from {transform: translateY(-20px); opacity: 0;}
        to {transform: translateY(0); opacity: 1;}
    }
    
    /* للهواتف */
    @media (max-width: 500px){
        .modal-content {
            padding: 20px;
        }
    }
    </style>

</style>
@endpush

@section('content')
<!-- PACKAGES SECTION -->
<form action="{{ route('ads.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="section">
        <h2>{{ __('messagess.packages_section_images') }}</h2>
        <label class="upload-btn" for="packages-input">{{ __('messagess.upload_packages') }}</label>
        <input type="hidden" name="page" value="packages">
        <input type="file" id="packages-input" name="images" accept="image/*">
        <button type="submit" class="upload-btn">{{ __('messagess.save') }}</button>
    
        <div class="image-grid" id="packages-grid">
            @foreach($packages as $pack)
                <div class="image-card">
                    <img src="{{ asset($pack->image) }}" alt="Ad Image">
    
                    <div class="image-actions">
                        @hasPermission('update_ads_status')
                            @if($pack->status == 1)
                                <button class="btn-toggle active" type="button" onclick="updateStatus({{ $pack->id }}, 1, this)">{{ __('messagess.active') }}</button>
                            @else
                                <button class="btn-toggle inactive" type="button" onclick="updateStatus({{ $pack->id }}, 0, this)">{{ __('messagess.inactive') }}</button>
                            @endif
                        @endhasPermission

                        @hasPermission('update_ads_link')
                            <button class="icon-btn" type="button" onclick="openLinkModal({{ $pack->id }}, '{{ $pack->link }}')">
                                <span class="iconify" data-icon="mdi:link" data-width="24" data-height="24"></span>
                            </button>
                        @endhasPermission

                        <button class="btn-delete" type="button" onclick="deleteImge({{ $pack->id }})">{{ __('messagess.delete') }}</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</form>

<!-- SERVICES SECTION -->
<form action="{{ route('ads.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="section">
        <h2>{{ __('messagess.services_section_images') }}</h2>
        <label class="upload-btn" for="services-input">{{ __('messagess.upload_services') }}</label>
        <input type="hidden" name="page" value="services">
        <input type="file" id="services-input" name="images" accept="image/*">
        <button type="submit" class="upload-btn">{{ __('messagess.save') }}</button>
    
        <div class="image-grid" id="services-grid">
            @foreach($services as $serve)
                <div class="image-card">
                    <img src="{{ asset($serve->image) }}" alt="Ad Image">
    
                    <div class="image-actions">
                        @if($serve->status == 1)
                            <button class="btn-toggle active" type="button" onclick="updateStatus({{ $serve->id }}, 1, this)">{{ __('messagess.active') }}</button>
                        @else
                            <button class="btn-toggle inactive" type="button" onclick="updateStatus({{ $serve->id }}, 0, this)">{{ __('messagess.inactive') }}</button>
                        @endif
                        
                        <button class="icon-btn" type="button" onclick="openLinkModal({{ $serve->id }}, '{{ $serve->link }}')">
                            <span class="iconify" data-icon="mdi:link" data-width="24" data-height="24"></span>
                        </button>
    
                        <button class="btn-delete" type="button" onclick="deleteImge({{ $serve->id }})">{{ __('messagess.delete') }}</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</form>

<!-- SHOP SECTION -->
<form action="{{ route('ads.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="section">
        <h2>{{ __('messagess.shop_section_images') }}</h2>
        <label class="upload-btn" for="shop-input">{{ __('messagess.upload_shop_images') }}</label>
        <input type="hidden" name="page" value="shop">
        <input type="file" id="shop-input" name="images" accept="image/*">
        <button type="submit" class="upload-btn">{{ __('messagess.save') }}</button>
    
        <div class="image-grid" id="shop-grid">
            @foreach($shop as $item)
                <div class="image-card">
                    <img src="{{ asset($item->image) }}" alt="Ad Image">
    
                    <div class="image-actions">
                        @if($item->status == 1)
                            <button class="btn-toggle active" type="button" onclick="updateStatus({{ $item->id }}, 1, this)">{{ __('messagess.active') }}</button>
                        @else
                            <button class="btn-toggle inactive" type="button" onclick="updateStatus({{ $item->id }}, 0, this)">{{ __('messagess.inactive') }}</button>
                        @endif

                        <button class="icon-btn" type="button" onclick="openLinkModal({{ $item->id }}, '{{ $item->link }}')">
                            <span class="iconify" data-icon="mdi:link" data-width="24" data-height="24"></span>
                        </button>
                        
                        <button class="btn-delete" type="button" onclick="deleteImge({{ $item->id }})">{{ __('messagess.delete') }}</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</form>
@endsection

<div id="linkModal" class="modal">
    <div class="modal-content">
        <h4>{{ __('messagess.ad_link') }}</h4>

        <input type="url" id="adLinkInput" placeholder="https://example.com">

        <div class="modal-actions">
            <button class="btn-save" onclick="saveLink()">{{ __('messagess.save') }}</button>
            <button class="btn-cancel" onclick="closeLinkModal()">{{ __('messagess.cancel') }}</button>
        </div>
    </div>
</div>


@push('after-scripts')
<script>
    function previewImages(input, containerId) {
        const container = document.getElementById(containerId);
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = () => {
                const div = document.createElement('div');
                div.classList.add('image-card');
                div.innerHTML = `
                    <img src="${reader.result}" alt="Uploaded Image">
                `;
                container.appendChild(div);

                div.querySelector('.btn-delete').addEventListener('click', () => {
                    div.remove();
                });

                const toggleBtn = div.querySelector('.btn-toggle');
                toggleBtn.addEventListener('click', () => {
                    toggleBtn.classList.toggle('inactive');
                    toggleBtn.textContent = toggleBtn.classList.contains('inactive') ? 'Inactive' : 'Active';
                });
            };
            reader.readAsDataURL(file);
        });
    }

    document.getElementById('packages-input').addEventListener('change', function() {
        previewImages(this, 'packages-grid');
    });

    document.getElementById('services-input').addEventListener('change', function() {
        previewImages(this, 'services-grid');
    });

    document.getElementById('shop-input').addEventListener('change', function() {
        previewImages(this, 'shop-grid');
    });
</script>
<!--change status script-->
<script>
function updateStatus(id, currentStatus, btn) {
    let newStatus = currentStatus == 1 ? 0 : 1;

    fetch(`/ads/update-status/${id}`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            if(newStatus == 1){
                btn.classList.remove('inactive');
                btn.classList.add('active');
                btn.innerText = 'Active';
                btn.setAttribute('onclick', `updateStatus(${id}, 1, this)`);
            } else {
                btn.classList.remove('active');
                btn.classList.add('inactive');
                btn.innerText = 'Inactive';
                btn.setAttribute('onclick', `updateStatus(${id}, 0, this)`);
            }
        } else {
            alert('حدث خطأ أثناء تغيير الحالة');
        }
    })
    .catch(err => {
        console.log(err);
        alert('حدث خطأ في الاتصال');
    });
}
</script>
<!--delete Imge script-->
<script>
    function deleteImge(id, btn) {

    if(!confirm('هل أنت متأكد من حذف هذه الصورة؟')) return;

    fetch(`/app/ads/destroy/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
    })
    .then(res => res.json())
    .then(data => {
        location.reload();
    })
    .catch(err => {
        console.log(err);
        alert('حدث خطأ في الاتصال');
    });
}

</script>
<script>
    let currentAdId = null;

    function openLinkModal(id, link) {
        currentAdId = id;
        document.getElementById('adLinkInput').value = link ?? '';
        document.getElementById('linkModal').style.display = 'flex';
    }

    function closeLinkModal() {
        document.getElementById('linkModal').style.display = 'none';
        currentAdId = null;
    }

    function saveLink() {
        let link = document.getElementById('adLinkInput').value;

    axios.put('/app/ads/update-link', {
        id: currentAdId,
        link: link
    })
    .then(res => {
        closeLinkModal();
        alert(res.data.message);
    })
    .catch(err => {
        console.error(err);
        alert('حدث خطأ أثناء تحديث الرابط');
    });

    }
</script>
<script src="https://code.iconify.design/2/2.2.1/iconify.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

@endpush
