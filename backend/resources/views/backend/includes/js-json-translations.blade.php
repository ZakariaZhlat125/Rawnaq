@php
    $jsonTranslationFile = lang_path(app()->getLocale().'.json');
    $jsonTranslations = is_file($jsonTranslationFile)
        ? (json_decode(file_get_contents($jsonTranslationFile), true) ?: [])
        : [];
@endphp
<script>
    window.localMessagesUpdate = {
        ...window.localMessagesUpdate,
        ...@json($jsonTranslations)
    };
</script>
