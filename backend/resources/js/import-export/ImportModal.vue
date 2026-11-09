<template>
  <BModal :title="$t('Import Data')" v-model="modal" centered>
    <a href="/" class="btn btn-secondary"><i class="fa-solid fa-indent"></i> {{ $t('admin_ui.download_format') }}</a>
  </BModal>
</template>
<script setup>
import {useModel} from '@/helpers/hooks/bootstrap-components'
const modal = useModel(() => {}, 'import_modal')
</script>
