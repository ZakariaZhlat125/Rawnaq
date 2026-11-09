{{-- loader start --}}
<div class="loader simple-loader sami-logo-loader">
    <div class="loader-body">
        <img src="{{ asset('images/samilogo.png') }}" alt="loader" class="sami-loader-logo">
    </div>
</div>
<style>
    .sami-logo-loader .loader-body {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #F5F6FA;
    }
    .sami-loader-logo {
        width: 90px;
        max-width: 38vw;
        height: auto;
        margin: 0 auto;
        transform-origin: center;
        animation: sami-logo-slide 1s ease-in-out infinite;
        filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.18));
    }
    @keyframes sami-logo-slide {
        0% {
            opacity: 0;
            transform: translateX(40px);
        }
        55% {
            opacity: 1;
            transform: translateX(0);
        }
        100% {
            opacity: 0;
            transform: translateX(-40px);
        }
    }
</style>
{{-- loader end --}}
