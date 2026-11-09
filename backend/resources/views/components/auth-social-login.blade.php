{{-- @if(config('sami.social.facebook') || config('sami.social.github') || config('sami.social.google'))
<div class="mb-4">
    <div class="text-center mt-2 mb-4">
        Sign in with social profiles
    </div>

    <div class="pb-4 text-center">
        @if(config('sami.social.facebook'))
        <x-button-a href="{{route('social.login', 'facebook')}}" class="btn-outline-secondary">
            <span class="">Facebook</span>
        </x-button-a>
        @endif

        @if(config('sami.social.github'))
        <x-button-a href="{{route('social.login', 'github')}}" class="btn-outline-secondary">
            <span class="">Github</span>
        </x-button-a>
        @endif

        @if(config('sami.social.google'))
        <x-button-a href="{{route('social.login', 'google')}}" class="btn-outline-secondary">
            <span class="">Google</span>
        </x-button-a>
        @endif
    </div>

    <hr>
</div>
@endif --}}
