<p class="m-0">{{ $serviceName }}</p>
<p class="m-0 fs-6"><strong>{{ $customerName }}</strong></p>
@if(!empty($branchName))
    <p class="m-0 fs-6">{{ $branchName }}</p>
@endif
@if(!empty($createdByName))
    <p class="m-0 fs-6">حجز بواسطة: {{ $createdByName }}</p>
@endif
