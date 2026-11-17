<script setup lang="ts">
import { t } from '@/i18n'
import AppDialog from './AppDialog.vue'
import { Button } from './ui/button'
import { confirmation, settleConfirmation } from '@/composables/useConfirm'
</script>
<template>
    <AppDialog
        :open="!!confirmation"
        :title="t(confirmation?.title)"
        :description="t(confirmation?.message)"
        @update:open="!$event && settleConfirmation(false)"
    >
        <div class="actions">
            <Button variant="outline" data-initial-focus @click="settleConfirmation(false)">{{
                t(confirmation?.cancel)
            }}</Button
            ><Button
                :variant="confirmation?.destructive ? 'destructive' : 'default'"
                @click="settleConfirmation(true)"
                >{{ t(confirmation?.confirm) }}</Button
            >
        </div>
    </AppDialog>
</template>
